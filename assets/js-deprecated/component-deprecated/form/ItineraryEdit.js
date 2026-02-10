import '../../../less-deprecated/itinerary-form.less';
import { PRESET_SEGMENT, SEGMENT_SELECTOR } from '../timeline/Notes';
import {listenAddNewPersonPopup} from '../../../bem/ts/service/listener';
import { render } from 'react-dom';
import Notes from '../timeline/Notes';
import React, { useEffect } from 'react';
/*eslint no-unused-vars: "jqueryui"*/
import jqueryui from 'jqueryui';

listenAddNewPersonPopup();

function ItineraryEdit(props) {

    let segment = {
        preset: PRESET_SEGMENT,
        notes: {
            text: props.text,
            files: props.files,
        },
    };

    return (<div>
        <Notes segment={segment} opened={true} form={props.form}/>
    </div>);
}

const $notesRow = $('.row-notes');
if ($notesRow.length) {
    $notesRow.after('<div id="notesEditor" class="editor-wrap"></div>');
    const contentElement = document.getElementById('notesEditor');
    const noteText = $(SEGMENT_SELECTOR, $notesRow).val();

    const $files = $('form[data-files]');
    const files = $files.data('files');

    $notesRow.hide();
    render(
        <React.StrictMode>
            <ItineraryEdit text={noteText} files={files} form={$notesRow.closest('form')}/>
        </React.StrictMode>,
        contentElement
    );
}