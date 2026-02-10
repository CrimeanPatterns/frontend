import $ from 'jquery-boot';
import ItineraryForm from 'pages/itinerary/form';

$(document).ready(function () {
    new ItineraryForm({
        formContainer: '#rental',
        template: '#segmentTemplate',
        autocompleteField: 'Address',
        autocompleteRoute: 'google_geo_code_airports',
        blockContainer: '#addedSegments',
        block: '.segment',
        companyField: { id: 'rentalCompany', kind: 3 },
    }).init();
});
