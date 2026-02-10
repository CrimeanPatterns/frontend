import $ from 'jquery-boot';
import ItineraryForm from 'pages/itinerary/form';

$(document).ready(function () {
    new ItineraryForm({
        formContainer: '#parking',
        template: '#segmentTemplate',
        autocompleteField: 'address',
        autocompleteRoute: 'google_geo_code_airports',
        blockContainer: '#addedSegments',
        block: '.segment',
        companyField: { id: 'parkingCompanyName', kind: window.parkingProviderKind },
    }).init();
});
