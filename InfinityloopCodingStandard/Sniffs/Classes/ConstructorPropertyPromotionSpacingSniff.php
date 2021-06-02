<?php

declare(strict_types = 1);

namespace InfinityloopCodingStandard\Sniffs\Classes;

class ConstructorPropertyPromotionSpacingSniff implements \PHP_CodeSniffer\Sniffs\Sniff
{
    public const CONSTRUCTOR_PARAMETER_SAME_LINE = 'ConstructorParametersOnSameLine';

    public function register() : array
    {
        return [\T_FUNCTION];
    }

    public function process(\PHP_CodeSniffer\Files\File $phpcsFile, $functionPointer) : void
    {
        if (!\SlevomatCodingStandard\Helpers\SniffSettingsHelper::isEnabledByPhpVersion(null, 80000)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        $namePointer = \SlevomatCodingStandard\Helpers\TokenHelper::findNextEffective($phpcsFile, $functionPointer + 1);

        if (\strtolower($tokens[$namePointer]['content']) !== '__construct') {
            return;
        }

        if (\SlevomatCodingStandard\Helpers\FunctionHelper::isAbstract($phpcsFile, $functionPointer)) {
            return;
        }

        $parameterPointers = $this->getParameterPointers($phpcsFile, $functionPointer);

        if (\count($parameterPointers) === 0) {
            return;
        }

        $containsPropertyPromotion = false;

        foreach ($parameterPointers as $parameterPointer) {
            $pointerBefore = \SlevomatCodingStandard\Helpers\TokenHelper::findPrevious(
                $phpcsFile,
                [\T_COMMA, \T_OPEN_PARENTHESIS],
                $parameterPointer - 1,
            );

            $visibilityPointer = \SlevomatCodingStandard\Helpers\TokenHelper::findNextEffective($phpcsFile, $pointerBefore + 1);

            if (\in_array($tokens[$visibilityPointer]['code'], \PHP_CodeSniffer\Util\Tokens::$scopeModifiers, true)) {
                $containsPropertyPromotion = true;
            }
        }

        if (!$containsPropertyPromotion) {
            return;
        }

        $previousPointer = null;

        foreach ($parameterPointers as $parameterPointer) {
            if ($previousPointer === null) {
                $previousPointer = $parameterPointer;

                continue;
            }

            if ($tokens[$previousPointer]['line'] !== $tokens[$parameterPointer]['line']) {
                continue;
            }

            $fix = $phpcsFile->addFixableError(
                'Constructor parameter should be reformatted to next line.',
                $parameterPointer,
                self::CONSTRUCTOR_PARAMETER_SAME_LINE,
            );

            if (!$fix) {
                continue;
            }

            $phpcsFile->fixer->beginChangeset();

            $pointerBefore = \SlevomatCodingStandard\Helpers\TokenHelper::findPrevious(
                $phpcsFile,
                [\T_COMMA, \T_OPEN_PARENTHESIS],
                $parameterPointer - 1,
            );

            $phpcsFile->fixer->addContent($pointerBefore, $phpcsFile->eolChar);

            $phpcsFile->fixer->endChangeset();
        }
    }

    private function getParameterPointers(\PHP_CodeSniffer\Files\File $phpcsFile, int $functionPointer) : array
    {
        $tokens = $phpcsFile->getTokens();

        return \SlevomatCodingStandard\Helpers\TokenHelper::findNextAll(
            $phpcsFile,
            \T_VARIABLE,
            $tokens[$functionPointer]['parenthesis_opener'] + 1,
            $tokens[$functionPointer]['parenthesis_closer'],
        );
    }
}
