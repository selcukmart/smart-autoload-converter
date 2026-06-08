<?php
/**
 * @author Selcuk Mart
 * 10.08.2022
 * 12:18
 */

namespace App\Converter\Directions\HelperTraits;

use App\Converter\Helpers\RegexDefinitions;
use function App\Helper\c;

trait DetermineClassnamesTrait
{

    private function determineColonClassMatches(): void
    {
        preg_match_all(RegexDefinitions::COLON_REGEX, $this->content, $colon_matches);
        $this->addToClassLists($colon_matches[count($colon_matches) - 1], 'COLON');
        preg_match_all(RegexDefinitions::COLON_REGEX_WITH_SLASH, $this->content, $colon_matches);
        $this->addToClassLists($colon_matches[count($colon_matches) - 1], 'COLON');
    }

    private function determineNewClassMatches(): void
    {
        preg_match_all(RegexDefinitions::NEW_REGEX, $this->content, $new_matches);
        $this->addToClassLists($new_matches[count($new_matches) - 1], 'NEW');

        preg_match_all(RegexDefinitions::NEW_REGEX_WITH_SLASH, $this->content, $new_matches);
        $this->addToClassLists($new_matches[count($new_matches) - 1], 'NEW');
    }

    private function determineExtendsClassMatches()
    {

        preg_match_all(RegexDefinitions::EXTENDS_REGEX, $this->content, $new_matches);
        $this->addToClassLists($new_matches[count($new_matches) - 1], 'EXTENDS');
        preg_match_all(RegexDefinitions::EXTENDS_WITH_BRACE_REGEX, $this->content, $new_matches);
        $this->addToClassLists($new_matches[count($new_matches) - 1], 'EXTENDS_WITH_BRACE');
    }

    private function determineImplementsClassMatches()
    {
        preg_match_all(RegexDefinitions::IMPLEMENTS_REGEX, $this->content, $new_matches);

        foreach ($new_matches[count($new_matches) - 1] as $key => $value) {
            $matchs = explode(',', $value);
            $this->addToClassLists($matchs, 'IMPLEMENTS');
        }
    }

    private function determineInstanceofClassMatches(): void
    {
        preg_match_all(RegexDefinitions::INSTANCE_OF_REGEX, $this->content, $colon_matches);
        $this->addToClassLists($colon_matches[count($colon_matches) - 1], 'INSTANCEOF');
    }

    private function determineCatchClassMatches(): void
    {
        preg_match_all(RegexDefinitions::CATCH_OF_REGEX, $this->content, $colon_matches);
        $this->addToClassLists($colon_matches[count($colon_matches) - 1], 'CATCH_EXCEPTION');
    }

    private function determineExceptionInsideCatchClassMatches(): void
    {
        preg_match_all(RegexDefinitions::EXCEPTION_IN_CATCH_REGEX, $this->content, $colon_matches);
        $this->addToClassLists($colon_matches[count($colon_matches) - 1], 'EXCEPTION_IN_CATCH');
        preg_match_all(RegexDefinitions::EXCEPTION_IN_CATCH_REGEX, $this->content, $colon_matches);
        $this->addToClassLists($colon_matches[count($colon_matches) - 1], 'EXCEPTION_IN_CATCH');
    }

}