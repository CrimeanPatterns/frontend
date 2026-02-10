import $ from 'jquery-boot';
import ItineraryForm from 'pages/itinerary/form';

$(document).ready(function () {
    new ItineraryForm({
        formContainer: window.ferryRideFormContainer,
        template: '#segmentTemplate',
        autocompleteField: 'Port',
        blockContainer: '#addedSegments',
        block: '.segment',
    }).init();
});
