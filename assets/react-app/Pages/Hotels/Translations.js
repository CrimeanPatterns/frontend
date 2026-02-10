import Translator from '@Services/Translator';

Translator.trans(/** @Desc("Award Hotel Search") */ 'award-hotel-search');

Translator.trans(
    /** @Desc("Find a hotel with the best points exchange offers") */ 'find-hotel-with-best-points-exchange',
);

Translator.trans(/** @Desc("Choose destination from hints") */ 'choose-destination-from-hints');

Translator.trans(/** @Desc("Choose check-in date") */ 'choose-check-in-date');

Translator.trans(/** @Desc("Choose check-out date") */ 'choose-check-out-date');

Translator.trans(/** @Desc("Where are you going?") */ 'where-are-you-going');

Translator.trans(/** @Desc("Check-in Date") */ 'check-in-date');

Translator.trans(/** @Desc("Check-out Date") */ 'check-out-date');

Translator.trans('search');

Translator.transChoice(/** @Desc("{0}rooms|{1}room|[2,Inf]rooms") */ 'rooms-count', 0);

Translator.transChoice(/** @Desc("{0}guests|{1}guest|[2,Inf]guests") */ 'guests-count', 1);

Translator.trans('itineraries.reservation.room_count', undefined, 'trips');

Translator.trans('itineraries.trip.adults', undefined, 'trips');

Translator.trans(/** @Desc("Children") */ 'children');

Translator.trans(/**@Desc("Hotel Programs") */ 'hotel-programs');

Translator.transChoice(/** @Desc("{0}brands|{1}brand|[2,Inf]brands") */ 'brand', 1);

Translator.trans(/** @Desc("Showing") */ 'showing');

Translator.trans(/** @Desc("Best Deals") */ 'best-deals');

Translator.trans(/**@Desc("Only show hotels bookable with my points") */ 'only-show-hotels-bookable-with-my-points');

Translator.trans('redemption-value');

Translator.trans(/** @Desc("Least Expensive") */ 'least-expensive');

Translator.trans(/** @Desc("Most Expensive") */ 'most-expensive');

Translator.trans('trips.distance', {}, 'trips');

Translator.trans(/** @Desc("Customer Rating") */ 'customer-rating');

Translator.trans(/** @Desc("km") */ 'km');

Translator.trans('redemption-value');

Translator.trans(/** @Desc("AwardWallet Assessment") */ 'awardwallet-assessment');

Translator.trans(/** @Desc("Avg / per Night") */ 'avg-per-night');

Translator.trans(/** @Desc("Points / per night") */ 'points-per-night');

Translator.trans(/** @Desc("Bad") */ 'bad');

Translator.trans(/** @Desc("Fair") */ 'fair');

Translator.trans(/** @Desc("Good") */ 'good');

Translator.trans(/** @Desc("Excellent") */ 'excellent');

Translator.trans(/** @Desc("Step") */ 'step');

Translator.trans(/** @Desc("Excellent Redemption Value") */ 'excellent-redemption-value');

Translator.trans(/** @Desc("Rated by AwardWallet") */ 'rated-by-awardwallet');

Translator.trans(/** @Desc("Check availability of the hotel offer") */ 'check-hotel-availability');

Translator.trans(/** @Desc("Select points to transfer") */ 'select-points-transfer');

Translator.trans(
    /** @Desc("You are %missing_points% points short to book this %cost%-point %brand% stay. We have selected some of the best
transfer options for you:") */ 'select-points-transfer-description',
    {
        missing_points: '',
        cost: '',
        brand: '',
    },
);

Translator.trans(/** @Desc("Current balance") */ 'current-balance');

Translator.trans(/** @Desc("Transfer points") */ 'transfer-points');

Translator.trans(/** @Desc("Book your hotel") */ 'book-your-hotel');

Translator.trans(/** @Desc("Book") */ 'book');

Translator.trans(/** @Desc("Check Availability") */ 'check-availability');

Translator.trans(/** @Desc("Book now") */ 'book-now');

Translator.trans(/** @Desc("You will transfer %amount% %brand% points") */ 'hotels-reward-transfer-description', {
    amount: '39,000',
    brand: 'chase',
});

Translator.transChoice(
    /** @Desc("{0}Other choices|{1}Other choice|[2,Inf]Other choices") */ 'other-choice',
    hotel.transferOptions.length,
);

Translator.trans(/** @Desc("No less than") */ 'no-less-than');

Translator.trans(/** @Desc("Not all providers are available.") */ 'search-hotel-not-all-providers-available');
