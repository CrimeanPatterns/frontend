<?php

namespace AwardWallet\MainBundle\Timeline\Item;

abstract class Type
{
    public const TRIP = 'trip';
    public const AIR_TRIP = 'airTrip';
    public const BUS_TRIP = 'busTrip';
    public const CRUISE_TRIP = 'cruiseTrip';
    public const FERRY_TRIP = 'ferryTrip';
    public const TRAIN_TRIP = 'trainTrip';
    public const CHECKIN = 'checkin';
    public const CHECKOUT = 'checkout';
    public const LAYOVER_CRUISE = 'layoverCruise';
    public const LAYOVER = 'layover';
    public const DATE = 'date';
    public const DROPOFF = 'dropoff';
    public const PICKUP = 'pickup';
    public const RESTAURANT = 'restaurant';
    public const PARKING_END = 'parkingEnd';
    public const PARKING_START = 'parkingStart';
    public const PLAN_END = 'planEnd';
    public const PLAN_START = 'planStart';
    public const TAXI_RIDE = 'taxi_ride';
    public const TRANSFER = 'transfer';
}
