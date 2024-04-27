<?php

namespace Module\Esunview\Payment;

use Module\Lipupini\Collection\Utility;
use Module\Lipupini\Exception;
use Module\Lipupini\State;
use Stripe;

require_once(__DIR__ . '/../vendor/stripe/stripe-php/init.php');

class Gateway {
	protected $stripe = null;

	public function __construct(private State $systemState) {
		Stripe\Stripe::setEnableTelemetry(false);
		$this->stripe = new Stripe\StripeClient($this->systemState->stripeKey);
	}

	public function itemPurchased(string $collectionName, string $collectionPath) {
		$id = $collectionName . '/' . $collectionPath;
		if (!empty($_GET['sid'])) {
			$lineItems = $this->stripe->checkout->sessions->allLineItems($_GET['sid']);
			if (empty($lineItems->data[0]->price->metadata->pathHash)) {
				throw new Exception('Could not get purchase information. Please contact apps@dup.bz to resolve this issue.');
			}
			if ($lineItems->data[0]->price->metadata->pathHash !== sha1($id)) {
				throw new Exception('Could not determine purchase information. Please contact apps@dup.bz to resolve this issue.');
			}
			$_SESSION['PURCHASED'][] = $id;
			// Keep the encoded `REQUEST_URI` for the redirect
			header('Location: ' . preg_replace('#[?&]sid=[^&]+#', '', $_SERVER['REQUEST_URI']));
			exit();
		}
		return !empty($_SESSION['PURCHASED']) && in_array($id, $_SESSION['PURCHASED']);
	}

	// Generate payment link and redirect
	public function redirectToPayment(string $collectionName, string $collectionFilePath) {
		$collectionUtility = new Utility($this->systemState);
		$productName = $collectionFilePath . ' @' . $collectionName;
		$itemUrl = $this->systemState->baseUri . '@' . $collectionName . '/' . $collectionFilePath . '.html';
		$product = $this->stripe->products->create(
			[
				'name' => $productName,
				'description' => 'High Quality Version',
				'images' => [
					$collectionUtility::urlEncodeUrl($collectionUtility->assetUrl($collectionName, 'image/thumbnail', $collectionFilePath))
				],
				'url' => $collectionUtility::urlEncodeUrl($itemUrl),
			],
			['idempotency_key' => 'ik-product-' . sha1($productName . $this->systemState->stripeKey)]
		);

		$price = $this->stripe->prices->create([
				'currency' => 'usd',
				'custom_unit_amount' => [
					'preset' => 500,
					'minimum' => 500,
					'enabled' => true,
				],
				'product' => $product->id,
				'metadata' => [
					'pathHash' => sha1($collectionName . '/'. $collectionFilePath),
				],
			],
			['idempotency_key' => 'ik-price-' . sha1($productName . $this->systemState->stripeKey)]
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
			['idempotency_key' => 'ik-payment-link-' . sha1($productName . $this->systemState->stripeKey)]
		);

		header('Location: ' . $paymentLink->url);
		exit();
	}
}
