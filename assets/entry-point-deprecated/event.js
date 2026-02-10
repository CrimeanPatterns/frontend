import $ from 'jquery-boot';
import ItineraryForm from 'pages/itinerary/form';

$(document).ready(function () {
    new ItineraryForm({
        formContainer: window.eventFormContainer,
        autocompleteField: 'address',
    }).init();
});
