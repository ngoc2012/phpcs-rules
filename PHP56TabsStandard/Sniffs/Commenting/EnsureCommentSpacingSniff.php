<?php
namespace PHP56TabsStandard\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class EnsureCommentSpacingSniff implements Sniff {
	public function register() {
		return [T_COMMENT];
	}

	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();
		$content = trim($tokens[$stackPtr]['content']);

		// Check if this is a section comment line ("// === ... ===")
		if (!preg_match('/^\/{2}\s*={2,}.*={2,}\s*$/', $content)) {
			return;
		}

		// ---- Prevent multiple triggers on 3-line blocks ----
		$line = $tokens[$stackPtr]['line'];
		$prevLine = $line - 1;

		// If previous line is also a section-header line → skip, not the first line
		foreach ($tokens as $t) {
			if ($t['line'] === $prevLine) {
				$prevContent = trim($t['content']);
				if (preg_match('/^\/{2}\s*={2,}.*={2,}\s*$/', $prevContent)) {
					return; // skip this, only process the FIRST line
				}
			}
		}

		// ======================================================
		// Now we know this is the FIRST line of the 3-line block
		// ======================================================

		// ===== Check: Two blank lines BEFORE =====
		$blankBefore = 0;

		for ($i = $stackPtr - 1; $i >= 0; $i--) {
			if ($tokens[$i]['line'] < $line - 2) {
				break;
			}
			if (trim($tokens[$i]['content']) === '') {
				$blankBefore++;
			}
		}

		if ($blankBefore < 2) {
			// $phpcsFile->addError(
			// 	'Section comment must be preceded by 2 blank lines.',
			// 	$stackPtr,
			// 	'NotEnoughBlankLinesBefore'
			// );
		}

		// ===== Check: One blank line AFTER =====
		$after1 = $this->isBlankLine($tokens, $line + 3); // after full block
		$after2 = $this->isBlankLine($tokens, $line + 4);

		if (!$after1 || $after2) {
			// $phpcsFile->addError(
			// 	'Section comment block must be followed by exactly 1 blank line.',
			// 	$stackPtr,
			// 	'InvalidBlankLinesAfter'
			// );
		}
	}

	private function isBlankLine(array $tokens, $line) {
		foreach ($tokens as $t) {
			if ($t['line'] === $line) {
				return trim($t['content']) === '';
			}
		}
		return false;
	}
}
