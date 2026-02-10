import $ from 'jquery-boot';
import ItineraryForm from 'pages/itinerary/form';

$(document).ready(function () {
    new ItineraryForm({
        formContainer: window.trainRideFormContainer,
        template: '#segmentTemplate',
        autocompleteField: 'Station',
        blockContainer: '#addedSegments',
        block: '.segment',
        companyField: { id: 'carrier', kind: 4 },
    }).init();
});
