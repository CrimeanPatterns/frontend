<?php

namespace AwardWallet\MainBundle\Tests;

use AwardWallet\MainBundle\Loyalty\ApiCommunicator;

class ApiCommunicatorMock extends ApiCommunicator
{
    public function getBalance($type)
    {
        switch ($type) {
            case 'rucaptcha':
                $balance = 16435.93;

                break;

            case 'antigate':
                $balance = 101.24;

                break;
        }

        return json_encode(['balance' => $balance ?? null]);
    }

    public function listRaAccount(?string $id = null)
    {
        $id = $id ?? 'all';

        switch ($id) {
            case 'all':
                $row = [
                    'id' => '6223758167bb234229b161fb',
                    'provider' => 'testprovider',
                    'login' => 'testLogin',
                    'login2' => null,
                    'login3' => null,
                    'password' => 'blablaPwd23',
                    'email' => 'bla@bla.com',
                    'code' => 1,
                    'state' => 1,
                    'lockState' => 0,
                    'lastUse' => date('Y-m-d H:i:s'),
                    'answers' => [],
                ];
                $rows[] = $row;
                $row = [
                    'id' => '6223758167bb234229b161ef',
                    'provider' => 'delta',
                    'login' => 'testLogin',
                    'login2' => null,
                    'login3' => null,
                    'password' => 'blablaPwd23',
                    'email' => 'bla@bla.com',
                    'code' => 11,
                    'state' => -1,
                    'lockState' => 0,
                    'lastUse' => date('Y-m-d H:i:s'),
                    'answers' => [],
                ];
                $rows[] = $row;
                $row = [
                    'id' => '6223758167bb234229b161ef',
                    'provider' => 'delta',
                    'login' => 'Barmaley@dd.com',
                    'login2' => null,
                    'login3' => null,
                    'password' => 'blablaPwd23',
                    'email' => 'bla@bla.com',
                    'code' => 9,
                    'state' => 2,
                    'lockState' => 0,
                    'lastUse' => date('Y-m-d H:i:s'),
                    'answers' => [],
                ];
                $rows[] = $row;
                $row = [
                    'id' => '6223758167bb22729b161fb3',
                    'provider' => 'testprovider',
                    'login' => 'Clava   ',
                    'login2' => null,
                    'login3' => null,
                    'password' => 'blablaPwd23',
                    'email' => 'bla@bla.com',
                    'code' => 1,
                    'state' => 1,
                    'lockState' => 0,
                    'lastUse' => date('Y-m-d H:i:s'),
                    'answers' => [],
                ];
                $rows[] = $row;

                break;

            default:
                $row = [
                    'id' => '6223758167bb22729b161fb3',
                    'provider' => 'testprovider',
                    'login' => 'Barmaley',
                    'login2' => null,
                    'login3' => null,
                    'password' => 'blablaPwd23',
                    'email' => 'bla@bla.com',
                    'code' => 1,
                    'state' => 1,
                    'lockState' => 0,
                    'lastUse' => date('Y-m-d H:i:s'),
                    'answers' => [],
                ];
                $rows[] = $row;

                break;
        }

        return json_encode(['rows' => $rows]);
    }

    public function getRaRegProvidersList()
    {
        return '{"providers_list":[{"provider":"spg","name":"Starwood Hotels (Preferred Guest)"},{"provider":"mileageplus","name":"United"}]}';
    }

    public function getRaRegisterResult(string $id)
    {
        return '{"status":1,"message":"Urah! You account is 2342350932"}';
    }

    public function getRaRegProviderFields($providerCode)
    {
        return '{"request_data":{"fields":{"Prefix":{"Type":"string","Caption":"Prefix","Required":true,"Options":{"0":"Mr","1":"Mrs"}},"FirstName":{"Type":"string","Caption":"First Name","Required":true,"Value":"John"},"LastName":{"Type":"string","Caption":"Last Name","Required":true},"AddressLine1":{"Type":"string","Caption":"Address Line 1","Required":true},"City":{"Type":"string","Caption":"City","Required":true},"Email":{"Type":"string","Caption":"Email address","Required":true},"Password":{"Type":"string","Caption":"Password. recommend: contain eight or more characters, including one lower case letter, one upper case letter, and one number or character such $ ! # & @ ? % + _","Required":true}}}}';
        //        return '{"message":"Not Found","logref":"62b9809112000"}';
    }

    public function sendRaRegister(array $data)
    {
        return '{"message":"Not Found","logref":"62b9809112000"}';
    }

    public function bulkRaAccountAction($ids, $method)
    {
        return '{"success":true}';
    }

    public function getListHotSession()
    {
        return '[{"id":"643d27c83e51be723d0a6b90","accountKey":"someTestAccount","startDate":"2023-04-17T11:49:40+00:00","lastUseDate":"2023-04-17T10:49:40+00:00","prefix":"someTestPrefix","provider":"testprovider","isLocked":false},{"id":"643d27c83e51be723d0a6b93","accountKey":"someTestAccount","startDate":"2023-04-17T10:50:40+00:00","lastUseDate":"2023-04-17T10:49:40+00:00","prefix":"someTestPrefix","provider":"testa17a79695cb0622b","isLocked":false},{"id":"643d27c83e51be723d0a6b94","accountKey":"someTestAccount","startDate":"2023-04-17T10:49:40+00:00","lastUseDate":"2023-04-17T10:49:40+00:00","prefix":"someTestPrefix","provider":"testa17a79695cb0622b","isLocked":false},{"id":"643d27c83e51be723d0a6b95","accountKey":"someTestAccount","startDate":"2023-04-17T11:02:40+00:00","lastUseDate":"2023-04-17T11:02:40+00:00","prefix":"someTestPrefix","provider":"testprovider","isLocked":false},{"id":"643d27c83e51be723d0a6b96","accountKey":"someTestAccount","startDate":"2023-04-17T11:02:40+00:00","lastUseDate":"2023-04-17T11:02:40+00:00","prefix":"someTestPrefix","provider":"testa17a79695cb0622b","isLocked":false},{"id":"643d27c83e51be723d0a6b32","accountKey":"testAccount","startDate":"2023-04-17T11:02:40+00:00","lastUseDate":"2023-04-17T11:02:40+00:00","prefix":"testPrefix","provider":"testa17a79695cb0622b","isLocked":true}]';
    }

    public function sendListToStopHotSessions(array $data)
    {
        return '{"success":true}';
    }
}
