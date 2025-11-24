<?php
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class ClassOrderSniff implements Sniff {
	public function register() {
		return [T_CLASS];
	}

	public function process(File $phpcsFile, $stackPtr) {
		$tokens = $phpcsFile->getTokens();
		$classStart = $tokens[$stackPtr]['scope_opener'];
		$classEnd = $tokens[$stackPtr]['scope_closer'];

		$constants = [];
		$properties = [];
		$constructors = [];
		$abstractMethods = [];
		$overriddenMethods = [];
		$publicMethods = [];
		$protectedMethods = [];
		$privateMethods = [];
		$gettersSetters = [];

		for ($i = $classStart + 1; $i < $classEnd; $i++) {
			if ($tokens[$i]['code'] === T_CONST) {
				$constants[] = $i;
			} elseif ($tokens[$i]['code'] === T_VAR || $tokens[$i]['code'] === T_PUBLIC || $tokens[$i]['code'] === T_PROTECTED || $tokens[$i]['code'] === T_PRIVATE) {
				$next = $phpcsFile->findNext(T_WHITESPACE, $i + 1, null, true);
				if ($tokens[$next]['code'] === T_VARIABLE) {
					$properties[] = $i;
				}
			} elseif ($tokens[$i]['code'] === T_FUNCTION) {
				$methodName = $phpcsFile->getDeclarationName($i);
				$methodProperties = $phpcsFile->getMethodProperties($i);

				if ($methodName === '__construct') {
					$constructors[] = $i;
				} elseif (isset($methodProperties['scope']) && $methodProperties['scope'] === 'abstract') {
					$abstractMethods[] = $i;
				} elseif (isset($methodProperties['scope_specified']) && $methodProperties['scope_specified'] === true) {
					// Check for @override in the method's doc comment
					$hasOverride = false;
					$docCommentPos = $phpcsFile->findPrevious(T_DOC_COMMENT, $i);
					if ($docCommentPos !== false) {
						$docComment = $phpcsFile->getTokensAsString($docCommentPos, ($i - $docCommentPos - 1));
						if (strpos($docComment, '@override') !== false) {
							$hasOverride = true;
						}
					}
					if ($hasOverride) {
						$overriddenMethods[] = $i;
					} elseif ($methodProperties['scope'] === 'public') {
						$publicMethods[] = $i;
					} elseif ($methodProperties['scope'] === 'protected') {
						$protectedMethods[] = $i;
					} elseif ($methodProperties['scope'] === 'private') {
						$privateMethods[] = $i;
					}
				}

				// Check for getters/setters (e.g., getXxx or setXxx)
				if (preg_match('/^(get|set)[A-Z]/', $methodName)) {
					$gettersSetters[] = $i;
				}
			}
		}

		// Verify order
		$this->verifyOrder($phpcsFile, $stackPtr, $constants, 'Constants', 0);
		$this->verifyOrder($phpcsFile, $stackPtr, $properties, 'Properties', count($constants));
		$this->verifyOrder($phpcsFile, $stackPtr, $constructors, 'Constructors', count($constants) + count($properties));
		$this->verifyOrder($phpcsFile, $stackPtr, $abstractMethods, 'Abstract methods', count($constants) + count($properties) + count($constructors));
		$this->verifyOrder($phpcsFile, $stackPtr, $overriddenMethods, 'Overridden methods', count($constants) + count($properties) + count($constructors) + count($abstractMethods));
		$this->verifyOrder($phpcsFile, $stackPtr, $publicMethods, 'Public methods', count($constants) + count($properties) + count($constructors) + count($abstractMethods) + count($overriddenMethods));
		$this->verifyOrder($phpcsFile, $stackPtr, $protectedMethods, 'Protected methods', count($constants) + count($properties) + count($constructors) + count($abstractMethods) + count($overriddenMethods) + count($publicMethods));
		$this->verifyOrder($phpcsFile, $stackPtr, $privateMethods, 'Private methods', count($constants) + count($properties) + count($constructors) + count($abstractMethods) + count($overriddenMethods) + count($publicMethods) + count($protectedMethods));
		$this->verifyOrder($phpcsFile, $stackPtr, $gettersSetters, 'Getters/Setters', count($constants) + count($properties) + count($constructors) + count($abstractMethods) + count($overriddenMethods) + count($publicMethods) + count($protectedMethods) + count($privateMethods));
	}

	protected function verifyOrder(File $phpcsFile, $stackPtr, $elements, $type, $expectedPosition) {
		if (!empty($elements)) {
			$firstElementPos = $elements[0];
			if ($firstElementPos < $expectedPosition) {
				$phpcsFile->addError(
					"The $type should be declared after position $expectedPosition.",
					$stackPtr,
					'IncorrectOrder'
				);
			}
		}
	}
}
