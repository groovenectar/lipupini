<?php

namespace Module\Esunview\Payment;

use Module\Lipupini\Collection\Utility;
use Module\Lipupini\Exception;
use Module\Lipupini\State;
use Stripe;

require_once(__DIR__ . '/../vendor/stripe/stripe-php/init.php');

class Gateway {
	protected $stripe = null;

	public function __construct(private State $system) {
		Stripe\Stripe::setEnableTelemetry(false);
		$this->stripe = new Stripe\StripeClient($this->system->stripeKey);
	}

	public function gateCheck($collectionName, $collectionPath): void {
		$dirName = pathinfo($collectionPath, PATHINFO_EXTENSION) ?
			pathinfo($collectionPath, PATHINFO_DIRNAME) : $collectionPath;
		if (
			static::gatedFolder($this->system->dirCollection . '/' . $collectionName . '/' . $dirName) &&
			static::gatedCollectionFolderClosed($collectionName, $dirName)
		) {
			header('Location: ' . $this->system->baseUriPath . 'g/' . rtrim($collectionName . '/' . $dirName, '/'));
			exit();
		}
	}

	public static function gatedCollectionFolderClosed($collectionName, $collectionFolder): bool {
		return empty($_SESSION['GATE_OPENED']) || !in_array(rtrim($collectionName . '/' . $collectionFolder, '/'), $_SESSION['GATE_OPENED']);
	}

	public static function gatedFolder($path) {
		return file_exists($path . '/.gated');
	}

	// @TODO: This is too similar to gateOpened()
	public function itemPurchased(string $collectionName, string $collectionPath) {
		$id = $collectionName . '/' . $collectionPath;
		if (!empty($_GET['sid'])) {
			$lineItems = $this->stripe->checkout->sessions->allLineItems($_GET['sid']);
			if (empty($lineItems->data[0]->price->metadata->pathHash)) {
				throw new Exception('Could not get purchase information. Please contact ' . $this->system->contactEmail . ' to resolve this issue.');
			}
			if ($lineItems->data[0]->price->metadata->pathHash !== sha1($id)) {
				throw new Exception('Could not determine purchase information. Please contact ' . $this->system->contactEmail . ' to resolve this issue.');
			}
			$_SESSION['PURCHASED'][] = $id;
			// Keep the encoded `REQUEST_URI` for the redirect
			header('Location: ' . preg_replace('#[?&]sid=[^&]+#', '', $_SERVER['REQUEST_URI']));
			exit();
		}
		return !empty($_SESSION['PURCHASED']) && in_array($id, $_SESSION['PURCHASED']);
	}

	// @TODO: This is too similar to itemPurchased()
	public function openGate(string $collectionName, string $collectionFolder) {
		$id = $collectionName . '/' . $collectionFolder;
		if (!empty($_GET['sid'])) {
			$lineItems = $this->stripe->checkout->sessions->allLineItems($_GET['sid']);
			if (empty($lineItems->data[0]->price->metadata->pathHash)) {
				throw new Exception('Could not get purchase information. Please contact ' . $this->system->contactEmail . ' to resolve this issue.');
			}
			if ($lineItems->data[0]->price->metadata->pathHash !== sha1($id)) {
				throw new Exception('Could not determine purchase information. Please contact ' . $this->system->contactEmail . ' to resolve this issue.');
			}
			$_SESSION['GATE_OPENED'][] = $id;
			// Keep the encoded `REQUEST_URI` for the redirect
			header('Location: ' . preg_replace('#[?&]sid=[^&]+#', '', $_SERVER['REQUEST_URI']));
			exit();
		}
	}

	// Generate payment link and redirect
	public function redirectToPaymentItem(string $collectionName, string $collectionFilePath) {
		$currency = 'usd';
		$price = 100;
		$collectionUtility = new Utility($this->system);
		$productName = $collectionFilePath . ' @' . $collectionName;
		$description = 'Thank you!';
		$itemUrl = $this->system->baseUri . '@' . $collectionName . '/' . $collectionFilePath . '.html';
		$image = $collectionUtility::urlEncodeUrl(
		$collectionUtility->thumbnailUrl($collectionName, 'image/thumbnail', $collectionFilePath, true) ?:
			$collectionUtility->thumbnailUrl($collectionName, 'video/thumbnail', $collectionFilePath, true) ?:
			$collectionUtility->thumbnailUrl($collectionName, 'audio/thumbnail', $collectionFilePath, true) ?:
			$collectionUtility->waveformUrl($collectionName, 'audio/waveform', $collectionFilePath, true)
		);
		$image = $image ? [$image] : [];
		$product = $this->stripe->products->create(
			[
				'name' => $productName,
				'description' => $description,
				'images' => $image,
				'url' => $collectionUtility::urlEncodeUrl($itemUrl),
			],
			['idempotency_key' => 'ik-product-' . sha1($productName . $description . $itemUrl . $this->system->stripeKey . '2')]
		);

		$price = $this->stripe->prices->create([
				'currency' => $currency,
				'custom_unit_amount' => [
					'preset' => $price,
					'minimum' => $price,
					'enabled' => true,
				],
				'product' => $product->id,
				'metadata' => [
					'pathHash' => sha1($collectionName . '/'. $collectionFilePath),
				],
			],
			['idempotency_key' => 'ik-price-' . sha1($price . $currency . $product->id . $this->system->stripeKey)]
		);

		$paymentLink = $this->stripe->paymentLinks->create([
				'line_items' => [
					[
						'price' => $price->id,
						'quantity' => 1,
					],
				],
				'after_completion' => [
					'type' => 'redirect',
					'redirect' => ['url' => $collectionUtility::urlEncodeUrl($itemUrl) . '?sid={CHECKOUT_SESSION_ID}'],
				],
				'payment_intent_data' => ['description' => $productName],
			],
			['idempotency_key' => 'ik-payment-link-' . sha1($productName . $price->id . $this->system->stripeKey)]
		);

		header('Location: ' . $paymentLink->url);
		exit();
	}

	// Generate payment link and redirect
	public function redirectToPaymentFolder(string $collectionName, string $collectionFolder, string $description) {
		$currency = 'usd';
		$price = 100;
		$collectionUtility = new Utility($this->system);
		$productName = $collectionFolder . '@' . $collectionName;
		$itemUrl = $this->system->baseUri . '@' . $collectionUtility::urlEncodeUrl($collectionName . '/' . $collectionFolder);
		$image = $collectionUtility::urlEncodeUrl($collectionUtility->assetUrl($collectionName, '', 'avatar.png', true));
		$image = $image ? [$image] : [];
		$product = $this->stripe->products->create(
			[
				'name' => $productName,
				'description' => $description,
				'images' => $image,
				'url' => $itemUrl,
			],
			['idempotency_key' => 'ik-product-' . sha1($productName . $description . $itemUrl . $this->system->stripeKey)]
		);

		$price = $this->stripe->prices->create([
			'currency' => $currency,
			'custom_unit_amount' => [
				'preset' => $price,
				'minimum' => $price,
				'enabled' => true,
			],
			'product' => $product->id,
			'metadata' => [
				'pathHash' => sha1($collectionName . '/'. $collectionFolder),
			],
		],
			['idempotency_key' => 'ik-price-' . sha1($price . $currency . $product->id . $this->system->stripeKey)]
		);

		$paymentLink = $this->stripe->paymentLinks->create([
			'line_items' => [
				[
					'price' => $price->id,
					'quantity' => 1,
				],
			],
			'after_completion' => [
				'type' => 'redirect',
				'redirect' => ['url' => $itemUrl . '?sid={CHECKOUT_SESSION_ID}'],
			],
			'payment_intent_data' => ['description' => $productName],
		],
			['idempotency_key' => 'ik-payment-link-' . sha1($productName . $price->id . $this->system->stripeKey)]
		);

		header('Location: ' . $paymentLink->url);
		exit();
	}
}
