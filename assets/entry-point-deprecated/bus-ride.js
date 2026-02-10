import $ from 'jquery-boot';
import ItineraryForm from 'pages/itinerary/form';

$(document).ready(function () {
    new ItineraryForm({
        formContainer: window.busRideFormContainer,
        template: '#segmentTemplate',
        autocompleteField: 'Station',
        blockContainer: '#addedSegments',
        block: '.segment',
    }).init();
});
