import $ from 'jquery-boot';
import ItineraryForm from 'pages/itinerary/form';

$(document).ready(function () {
    new ItineraryForm({
        formContainer: window.taxiRideFormContainer,
        template: '#segmentTemplate',
        autocompleteField: 'Address',
        blockContainer: '#addedSegments',
        block: '.segment',
    }).init();
});
