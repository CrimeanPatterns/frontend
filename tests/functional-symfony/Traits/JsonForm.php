<?php

namespace AwardWallet\Tests\FunctionalSymfony\Traits;

trait JsonForm
{
    protected function grabFormCsrfToken(\TestSymfonyGuy $I)
    {
        return $I->grabDataFromResponseByJsonPath('$.children[?(@.name = "_token")].value')[0];
    }

    /**
     * @param string $name
     */
    protected function grabFieldError(\TestSymfonyGuy $I, $name)
    {
        return $I->grabDataFromResponseByJsonPath('$..[?(@.name = "' . $name . '")].errors[0]')[0];
    }

    protected function grabFormError(\TestSymfonyGuy $I)
    {
        return $I->grabDataFromResponseByJsonPath('$.errors[0]')[0];
    }
}
