<?php

namespace AwardWallet\MainBundle\Email;

class VersionConverter
{
    public function convert(&$data)
    {
        $data['info'] = $data['metadata'];
        $data['realInfo'] = $data['nestedEmailMetadata'];

        if (!empty($data['itineraries'])) {
            foreach ($data['itineraries'] as &$it) {
                $this->convertCommon($it);

                switch ($it['type']) {
                    case 'flight':
                        $this->convertFlight($it);

                        break;

                    case 'hotelReservation':
                        $this->convertHotel($it);

                        break;

                    case 'carRental':
                        $this->convertRental($it);

                        break;

                    case 'event':
                        $this->convertEvent($it);

                        break;

                    case 'train':
                    case 'transfer':
                    case 'bus':
                        $this->convertTransport($it);

                        break;

                    case 'cruise':
                        $this->convertCruise($it);

                        break;

                    default:
                        $it = null;
                }
            }
            $data['itineraries'] = array_filter($data['itineraries']);
        }

        if (!empty($data['loyaltyAccount'])) {
            $data['loyaltyProgram'] = $data['loyaltyAccount'];
            $props = [];

            if (!empty($data['loyaltyProgram']['properties'])) {
                foreach ($data['loyaltyProgram']['properties'] as $p) {
                    $props[$p['code']] = $p['value'];
                }

                if (isset($data['loyaltyProgram']['balance'])) {
                    $props['Balance'] = $data['loyaltyProgram']['balance'];
                }
            }
            $data['loyaltyProgram']['properties'] = $props;

            if (!empty($data['loyaltyProgram']['history'])) {
                $new = [];

                foreach ($data['loyaltyProgram']['history'] as $row) {
                    $newRow = [];

                    foreach ($row['fields'] as $field) {
                        $newRow[] = [$field['name'], $field['value']];
                    }
                    $new[] = $newRow;
                }
                $data['loyaltyProgram']['activity'] = $new;
            }
        }

        return $data;
    }

    private function convertCommon(&$data)
    {
        $data['providerDetails'] = [
            'confirmationNumber' => $data['confirmationNumbers'][0]['number'] ?? null,
            'tripNumber' => !empty($data['travelAgency']['confirmationNumbers']) ? $data['travelAgency']['confirmationNumbers'][0]['number'] : null,
            'accountNumbers' => !empty($data['providerInfo']['accountNumbers']) ? $this->numbers($data['providerInfo']['accountNumbers']) : null,
            'reservationDate' => $data['reservationDate'] ?? null,
            'status' => $data['status'] ?? null,
            'name' => $data['providerInfo']['name'] ?? $data['travelAgency']['providerInfo']['name'] ?? null,
            'code' => $data['providerInfo']['code'] ?? $data['travelAgency']['providerInfo']['code'] ?? null,
        ];
        $data['totalPrice'] = !empty($data['pricingInfo']) ? $this->arr($data['pricingInfo'], ['total', 'cost', 'spentAwards', 'currencyCode']) : null;

        if (!empty($data['pricingInfo']['fees'])) {
            foreach ($data['pricingInfo']['fees'] as $fee) {
                if ((strcasecmp($fee['name'], 'Tax') === 0 || strcasecmp($fee['name'], 'Taxes') === 0) && !isset($data['totalPrice']['tax'])) {
                    $data['totalPrice']['tax'] = $fee['charge'];
                } else {
                    $data['totalPrice']['fees'][] = $fee;
                }
            }
        }
        $data['totalPrice']['earnedAwards'] = $data['providerInfo']['earnedRewards'] ?? null;
    }

    private function convertFlight(&$data)
    {
        $locator = null;

        foreach ($data['segments'] as &$segment) {
            if (!empty($segment['marketingCarrier'])) {
                if (null === $locator) {
                    $locator = $segment['marketingCarrier']['confirmationNumber'];
                }
                $segment['flightNumber'] = $segment['marketingCarrier']['flightNumber'] ?? null;
                $segment['airlineName'] = $segment['marketingCarrier']['airline']['name'] ?? null;

                if (!empty($segment['operatingCarrier']['airline']['name'])) {
                    $segment['operator'] = $segment['operatingCarrier']['airline']['name'];
                } elseif (!empty($segment['wetleaseCarrier']['name'])) {
                    $segment['operator'] = $segment['wetleaseCarrier']['name'];
                }
                $segment['aircraft'] = $segment['aircraft']['name'] ?? null;
            }
        }
        $data['providerDetails']['confirmationNumber'] = $locator;

        if (!empty($data['travelers'])) {
            $this->names($data['travelers']);
        }

        if (!empty($data['issuingCarrier']['ticketNumbers'])) {
            $data['ticketNumbers'] = $this->numbers($data['issuingCarrier']['ticketNumbers']);
        }
    }

    private function convertHotel(&$data)
    {
        if (!empty($data['guests'])) {
            $this->names($data['guests']);
        }

        if (!empty($data['rooms'])) {
            foreach ($data['rooms'] as $room) {
                if (isset($room['rate']) && !isset($data['totalPrice']['rate'])) {
                    $data['totalPrice']['rate'] = $room['rate'];
                }

                if (isset($room['rateType']) && !isset($data['totalPrice']['rateType'])) {
                    $data['totalPrice']['rateType'] = $room['rateType'];
                }
            }
        }
    }

    private function convertEvent(&$data)
    {
        if (!empty($data['guests'])) {
            $this->names($data['guests']);
        }
    }

    private function convertRental(&$data)
    {
        if (!empty($data['driver']['name'])) {
            $data['driver']['fullName'] = $data['driver']['name'];
        }
    }

    private function convertTransport(&$data)
    {
        $transport = ['bus' => 'bus', 'train' => 'train', 'transfer' => 'transport'][$data['type']];
        $data['type'] = 'transportation';

        if (!empty($data['segments'])) {
            foreach ($data['segments'] as &$s) {
                $s['transport'] = [
                    'type' => $transport,
                    'name' => $s['busInfo']['type'] ?? null,
                ];

                if (!isset($s['airportCode'])) {
                    $s['airportCode'] = $s['stationCode'] ?? null;
                }
                $base = $s['serviceName'] ?? $s['trainInfo']['type'] ?? null;

                if (isset($base)) {
                    $s['transport']['name'] = $base;

                    if (isset($s['scheduleNumber']) && stripos($base, $s['scheduleNumber']) === false) {
                        $s['transport']['name'] .= ' ' . $base;
                    }
                }
            }
        }

        if (!empty($data['ticketNumbers'])) {
            $data['ticketNumbers'] = $this->numbers($data['ticketNumbers']);
        }

        if (!empty($data['travelers'])) {
            $this->names($data['travelers']);
        }
    }

    private function convertCruise(&$data)
    {
        if (!empty($data['travelers'])) {
            $this->names($data['travelers']);
        }
    }

    private function arr($arr, $keys)
    {
        return array_intersect_key($arr, array_flip($keys));
    }

    private function numbers(array $numbers)
    {
        return array_map(function ($n) {return $n['number']; }, $numbers);
    }

    private function names(&$arr)
    {
        foreach ($arr as &$item) {
            $item['fullName'] = $item['name'];
        }
    }
}
