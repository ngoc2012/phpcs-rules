<?php

namespace PHP56TabsStandard\Sniffs\Methods;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class SameLineBraceSniff implements Sniff {
	public function register() {
		// Apply to methods, classes, interfaces, traits
		return [
			T_FUNCTION,
			T_CLASS,
			T_INTERFACE,
			T_TRAIT,
		];
	}

	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();

		// Skip closures (for functions)
		if ($tokens[$stackPtr]['code'] === T_FUNCTION &&
		isset($tokens[$stackPtr]['conditions'])
		) {
			foreach ($tokens[$stackPtr]['conditions'] as $cond => $type) {
				if ($type === T_CLOSURE) {
					return;
				}
			}
		}

		// Must have a scope opener (real brace)
		if (!isset($tokens[$stackPtr]['scope_opener'])) {
			return;
		}

		$openingBrace = $tokens[$stackPtr]['scope_opener'];

		// Determine line to compare
		if ($tokens[$stackPtr]['code'] === T_FUNCTION) {

			// Find the closing parenthesis of the function arguments
			if (!isset($tokens[$stackPtr]['parenthesis_closer'])) {
				return; // invalid function
			}

			$comparePtr = $tokens[$stackPtr]['parenthesis_closer'];
		}
		else if (in_array($tokens[$stackPtr]['code'], [T_CLASS, T_INTERFACE, T_TRAIT])) {

			// Find the class/interface/trait name token
			$namePtr = $phpcsFile->findNext(T_STRING, $stackPtr);
			if (!$namePtr) {
				return;
			}

			$comparePtr = $namePtr;
		}
		else {
			return;
		}

		$compareLine = $tokens[$comparePtr]['line'];
		$braceLine    = $tokens[$openingBrace]['line'];

		if ($braceLine !== $compareLine) {

			$kind = $tokens[$stackPtr]['code'] === T_FUNCTION ? 'method' : 'class';

			$phpcsFile->addError(
				"Opening brace of the $kind must be on the same line as the declaration",
				$openingBrace,
				'BraceNotSameLine'
			);
		}
	}
}
