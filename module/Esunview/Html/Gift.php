<?php

require(__DIR__ . '/Core/Open.php') ?>

<div class="centered-content">
	<div>
		<?php if ($this->word === 'g') : ?>

		<p>Try to click on your favorite word:</p>
		<?php endif ?>

		<form class="purchase" action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="get">
			<div>
				<input type="hidden" name="provider" value="stripe">
				<input type="hidden" name="word" value="<?php echo $this->word ?>">
				<button type="submit"><?php echo htmlentities(ucfirst($this->word)) ?></button>
			</div>
		</form>
	</div>
</div>

<script>
	let currentWord = 0
	let baseTitle = <?php echo json_encode(preg_replace('#^' . preg_quote(ucfirst($this->word)) . '#', '', $this->pageTitle)) ?>

	const capitalizeFirstLetter = (string) => {
		return string.charAt(0).toUpperCase() + string.slice(1);
	}
	const g = () => {
		const button = document.querySelector('.purchase button');

		const interval = setInterval(() => {
			let word = capitalizeFirstLetter(words[currentWord])
			document.title = word + baseTitle
			button.innerText = word
			document.querySelector('.purchase [name=word]').value = words[currentWord]
			if (currentWord === words.length - 1) {
				currentWord = 0
			}
			currentWord++
		}, 200)
		button.addEventListener('click', () => clearInterval(interval))
	}
	<?php if ($this->word === 'g') : ?>g()<?php endif ?>

</script>
<?php

require(__DIR__ . '/Core/Close.php');
