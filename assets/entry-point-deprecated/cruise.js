import $ from 'jquery-boot';
import ItineraryForm from 'pages/itinerary/form';

$(document).ready(function () {
    new ItineraryForm({
        formContainer: '#cruise',
        template: '#segmentTemplate',
        autocompleteField: 'Port',
        blockContainer: '#addedSegments',
        block: '.segment',
        companyField: { id: 'cruiseShip', kind: 10 },
    }).init();
});
