<?php

namespace AwardWallet\Tests\FunctionalSymfony\_steps\Mobile;

abstract class MobileApiAbstractSteps extends \TestSymfonyGuy
{
    public const ALPHABET = 'abcdefghijklmnopqrstuvwxyz0123456789';
    /**
     * @var array
     */
    protected static $routes = [];

    /**
     * @var string
     */
    protected static $apiPath = '/m/api';

    /**
     * @var string
     */
    protected $fakeDataPath;

    public function __construct(\Codeception\Scenario $scenario)
    {
        parent::__construct($scenario);

        $this->haveHttpHeader('Content-Type', 'application/json');
        $this->haveHttpHeader('Accept', 'application/json');

        $this->fakeDataPath = __DIR__ . "/../../../_data";
    }

    /**
     * @return string
     */
    public static function getApiPath()
    {
        return self::$apiPath;
    }

    public static function getUrl($routeName)
    {
        if (!isset(static::$routes[$routeName])) {
            throw new \InvalidArgumentException('Unknown url');
        }

        return self::$apiPath . call_user_func_array(
            'sprintf',
            array_merge(
                [
                    static::$routes[$routeName],
                ],
                array_slice(func_get_args(), 1)
            )
        );
    }

    protected function checkFormErrors(array $expectedErrors)
    {
        $I = $this;

        foreach ($expectedErrors as $expectedError) {
            if (!is_array($expectedError)) {
                $expectedError = [$expectedError, null];
            }

            if (!array_key_exists(1, $expectedError)) {
                $expectedError[1] = null;
            }
            [$path, $messages] = $expectedError;

            if (!is_array($messages)) {
                $messages = [$messages];
            }
            $I->assertNotNull($path);
            $I->assertTrue(is_array($errors = (\is_callable($path) ? $path($I) : $I->grabDataFromJsonResponse("{$path}.errors"))));
            $I->assertNotEmpty($errors);

            // error message check
            foreach ($messages as $message) {
                if (isset($message)) {
                    $this->assertContains($message, $errors);
                }
            }
        }
    }
}
