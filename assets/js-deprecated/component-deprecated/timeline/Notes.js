var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
/**
 * compile tsconfig.json with:
 *     "noImplicitAny": false,
 *     "strictNullChecks": false,
 *     "strictPropertyInitialization": false,
 *     "strictFunctionTypes": false,
 *     "noImplicitThis": false,
 *     "strictBindCallApply": false,
 *     "noPropertyAccessFromIndexSignature": false,
 *     "noUncheckedIndexedAccess": false,
 *     "noImplicitReturns": false,
 *     "noFallthroughCasesInSwitch": false,
 *     "noUnusedLocals": false,
 *     "noUnusedParameters": false
 */
import '../../../../web/assets/awardwalletnewdesign/less/partials/icon.less';
import './Notes.less';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
import { CKEditor } from '@ckeditor/ckeditor5-react';
import { extractOptions } from '../../../bem/ts/service/env';
import { formatFileSize } from "../../../bem/ts/service/formatter";
import { useDropzone } from 'react-dropzone';
import API from '../../../bem/ts/service/axios';
import ClassicEditor from '../../../../web/assets/common/js/ckeditor5/build/ckeditor';
import React, { useEffect, useState } from 'react';
import Routing from '../../../bem/ts/service/router';
import Translator from '../../../bem/ts/service/translator';
import classNames from 'classnames';
const locale = extractOptions().locale;
export const PRESET_PLAN = 'plan';
export const PRESET_SEGMENT = 'segment';
export const SEGMENT_SELECTOR = 'textarea[name$="[notes]"]';
const ROUTE_PLAN_UPLOAD_FILE = 'aw_timeline_plan_upload_file';
const ROUTE_SEGMENT_UPLOAD_FILE = 'upload_note_file';
let isSegment = false;
const isAdding = -1 !== location.href.indexOf('/add');
const _Notes = ({ segment, opened, form = null }) => {
    var _a;
    const preset = (segment === null || segment === void 0 ? void 0 : segment.preset) || PRESET_PLAN;
    if (PRESET_SEGMENT === preset) {
        opened = true;
        isSegment = true;
    }
    const [noteText, setNoteText] = useState((_a = segment.notes) === null || _a === void 0 ? void 0 : _a.text);
    if (PRESET_PLAN === preset && undefined !== noteText && noteText.length > 0) {
        opened = true;
    }
    const [isOpen, setIsOpen] = useState(opened);
    const [isEdit, setEdit] = useState(undefined === noteText || 0 === noteText.length || isSegment ? true : false);
    const [isLoading, setLoading] = useState(false);
    const [editor, setEditor] = useState(null);
    const [noteFiles, setNoteFiles] = useState(Object.values(segment.notes.files));
    const [tmpNoteFiles, setTmpNoteFiles] = useState([]);
    const [attachFileInfo, setAttachFileInfo] = useState('');
    const [errorText, setErrorText] = useState('');
    const _noteFiles = [];
    noteFiles.map((file) => _noteFiles[file.id] = file.description || '');
    const [saveNoteFiles, setSaveNoteFiles] = useState(_noteFiles);
    const { getRootProps, getInputProps } = useDropzone({
        maxFiles: 1,
        multiple: false,
        onDrop: function (acceptedFiles, fileRejections) {
            if (acceptedFiles.length) {
                uploadFile(acceptedFiles, segment, setNoteFiles, setAttachFileInfo, setLoading, setErrorText, tmpNoteFiles, setTmpNoteFiles);
            }
            if (fileRejections.length) {
                fileRejections.map((file) => {
                    const error = file.errors[0];
                    if (error) {
                        if (error.code.includes('invalid-type')) {
                            setErrorText(Translator.trans('card-pictures.error.file-type').replace('$0', 'jpeg, webp, png, txt, csv, doc, xls, pdf, rtf, ppt'));
                        }
                        else if (error.code.includes('too-large')) {
                            setErrorText(Translator.trans('card-pictures.error.big-file').replace('$0', '4'));
                        }
                    }
                });
            }
        },
        maxSize: 16777216,
        accept: {
            'image/jpeg': [],
            'image/png': [],
            'image/webp': [],
            'text/plain': [],
            'text/csv': [],
            'application/msword': [],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document': [],
            'application/vnd.ms-excel': [],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': [],
            'application/vnd.ms-powerpoint': [],
            'application/vnd.oasis.opendocument.presentation': [],
            'application/vnd.oasis.opendocument.text': [],
            'application/rtf': [],
            'application/pdf': [],
        }
    });
    useEffect(() => {
        if (isSegment) {
            const $fieldError = $(SEGMENT_SELECTOR).closest('.row-notes');
            if ($fieldError.hasClass('error')) {
                setErrorText($fieldError.find('.error-message .error-message-description').text());
            }
        }
    });
    return (React.createElement("div", { className: classNames({
            'notes-container': true,
            'opened': isOpen,
            'loading': isLoading,
            'js-notes-filled': (undefined !== noteText && noteText.length > 0) || noteFiles.length > 0,
        }), "data-loading": Translator.trans('please-wait') },
        React.createElement("div", Object.assign({ className: "header" }, (PRESET_PLAN === preset) && { onClick: () => setIsOpen(!isOpen) }),
            React.createElement("i", { className: "icon-silver-arrow-down" }),
            React.createElement("i", { className: "icon-memo head-icon" }),
            Translator.trans('notes')),
        React.createElement("div", { className: "body" },
            React.createElement("div", { className: "content" },
                React.createElement(Content, { isEdit: isEdit, text: noteText, setEditor: setEditor, form: form, preset: preset, errorText: errorText, setErrorText: setErrorText })),
            React.createElement("div", { className: "actions" },
                React.createElement(Actions, { isEdit: isEdit, setEdit: setEdit, setLoading: setLoading, setNoteText: setNoteText, editor: editor, segment: segment, saveNoteFiles: saveNoteFiles, setNoteFiles: setNoteFiles, errorText: errorText, setErrorText: setErrorText, preset: preset, setAttachFileInfo: setAttachFileInfo })),
            React.createElement("div", { className: "attachments" },
                tmpNoteFiles.length > 0 ?
                    React.createElement("div", { className: "plan-files" }, tmpNoteFiles.map((file) => React.createElement(Attachment, { key: file.id, file: file, isEdit: isEdit, isTemp: true, noteFiles: tmpNoteFiles, setNoteFiles: setTmpNoteFiles, saveNoteFiles: saveNoteFiles, setSaveNoteFiles: setSaveNoteFiles, setLoading: setLoading })))
                    : null,
                noteFiles.length > 0 ?
                    React.createElement("div", { className: "plan-files" }, noteFiles.map((file) => React.createElement(Attachment, { key: file.id, file: file, isEdit: isEdit, isTemp: false, noteFiles: noteFiles, setNoteFiles: setNoteFiles, saveNoteFiles: saveNoteFiles, setSaveNoteFiles: setSaveNoteFiles, setLoading: setLoading })))
                    : null,
                isEdit
                    ? React.createElement("div", Object.assign({ id: "attachFieldWrap", "data-dropfiles": Translator.trans('drop-files-here'), className: classNames({
                            'attach-field': true,
                            'attach-zone': true,
                        }) }, getRootProps()),
                        React.createElement("button", { id: "attachFileBtn", className: "btn-silver", type: "button" }, Translator.trans('choose-file')),
                        React.createElement("span", null, attachFileInfo),
                        React.createElement("input", Object.assign({ id: "attachFile", name: "attachFile", type: "file" }, getInputProps())))
                    : null))));
};
const Content = React.memo(props => {
    var _a;
    if (!props.isEdit) {
        if (null === ((props === null || props === void 0 ? void 0 : props.text) || null)) {
            return React.createElement(React.Fragment, null);
        }
        let text = (_a = props.text) !== null && _a !== void 0 ? _a : '';
        return React.createElement("div", { dangerouslySetInnerHTML: { __html: linkify(text) } });
    }
    return React.createElement(CKEditor, { editor: ClassicEditor, config: {
            toolbar: [
                'bold', 'italic', 'underline', '|',
                'undo', 'redo'
            ]
        }, data: props.text, onReady: editor => {
            editor.editing.view.document.on('enter', (evt, data) => {
                data.preventDefault();
                evt.stop();
                editor.execute('shiftEnter');
            }, { priority: 'high' });
            editor.editing.view.document.on('clipboardInput', (evt, data) => {
                let text = data.dataTransfer.getData('text/plain');
                text = text.replace(/(?:\r\n|\r|\n)/g, '<br>');
                data.content = editor.data.htmlProcessor.toView(text);
            });
            props.setEditor(editor);
            if (isSegment && props.form) {
                props.form.submit(() => {
                    $(SEGMENT_SELECTOR, props.form).val(editor.getData());
                });
            }
        }, onChange: (event, editor) => {
            if (isSegment && props.form && 'flight' === props.form.attr('id')) {
                $(SEGMENT_SELECTOR, props.form).val(editor.getData());
            }
            if (editor.getData().length >= 4000) {
                props.setErrorText(Translator.transChoice('maxlength', 4000, { limit: 4000 }, 'validators'));
            }
            else if (props.errorText !== '') {
                props.setErrorText('');
            }
        } });
});
const Actions = React.memo(props => {
    if (!props.isEdit) {
        return (React.createElement("div", null,
            React.createElement("button", { className: "btn-silver", onClick: () => props.setEdit(!props.isEdit), type: "button" },
                React.createElement("i", { className: "icon-edit" }),
                React.createElement("span", null, Translator.trans('button.edit'))),
            '' !== props.errorText && props.isEdit
                ? React.createElement("div", { className: "notes-error" },
                    React.createElement("i", { className: "icon-warning-small" }),
                    " ",
                    props.errorText)
                : null));
    }
    return (React.createElement("div", null,
        isSegment
            ? null
            : React.createElement("button", { className: "btn-silver", type: "button", onClick: () => {
                    props.setEdit(!props.isEdit);
                    props.setErrorText('');
                } }, Translator.trans('button.cancel')),
        isSegment
            ? null
            : React.createElement("button", { className: "btn-blue", type: "button", onClick: () => {
                    if ($('.notes-error', '.notes-container').length) {
                        $('html, body').animate({ scrollTop: $('.notes-error', '.notes-container').offset().top - 100 }, 500);
                        return false;
                    }
                    props.setEdit(false);
                    updateNote(props);
                    props.setErrorText('');
                } }, Translator.trans('form.button.save')),
        '' !== props.errorText && props.isEdit
            ? React.createElement("div", { className: "notes-error" },
                React.createElement("i", { className: "icon-warning-small" }),
                " ",
                props.errorText)
            : null));
});
Actions.displayName = 'Actions';
const Attachment = React.memo(props => {
    const file = props.file;
    const date = new Intl.DateTimeFormat(locale.replace('_', '-'), {
        dateStyle: 'medium',
        timeStyle: 'short'
    }).format(Date.parse(file.uploadDate));
    if (!props.isEdit) {
        const fileUrl = isSegment
            ? Routing.generate('aw_timeline_itinerary_fetch_file', { itineraryFileId: file.id })
            : Routing.generate('aw_timeline_plan_fetch_file', { planFileId: file.id });
        return (React.createElement("div", { className: "plan-file" },
            props.isTemp
                ? React.createElement(React.Fragment, null,
                    React.createElement("span", null,
                        React.createElement("i", { className: "icon-clip" }),
                        file.fileName),
                    React.createElement("a", { className: "file-remove", onClick: () => confirmRemoveFile(file.id, props.noteFiles, props.setNoteFiles, props.setLoading), href: "#", title: Translator.trans('card-pictures.label.remove') },
                        React.createElement("i", { className: "icon-delete" })))
                : React.createElement(React.Fragment, null,
                    React.createElement("a", { href: fileUrl, target: "planFile" },
                        React.createElement("i", { className: "icon-clip" }),
                        file.fileName),
                    React.createElement("a", { className: "file-remove", onClick: () => confirmRemoveFile(file.id, props.noteFiles, props.setNoteFiles, props.setLoading), href: "#", title: Translator.trans('card-pictures.label.remove') },
                        React.createElement("i", { className: "icon-delete" }))),
            (file === null || file === void 0 ? void 0 : file.description) ? React.createElement("span", { className: "file-desc" }, file.description) : null,
            React.createElement("span", { className: "file-size" },
                "(",
                formatFileSize(file.fileSize),
                ")"),
            React.createElement("span", { className: "file-date" }, date)));
    }
    const fileUrl = isSegment
        ? Routing.generate('aw_timeline_itinerary_fetch_file', { itineraryFileId: file.id })
        : Routing.generate('aw_timeline_plan_fetch_file', { planFileId: file.id });
    return (React.createElement("div", { className: "plan-file-edit" },
        React.createElement("div", { className: "file-edit-name" }, props.isTemp
            ? React.createElement(React.Fragment, null,
                React.createElement("span", null,
                    React.createElement("i", { className: "icon-clip" }),
                    file.fileName),
                React.createElement("a", { className: "file-remove", onClick: () => confirmRemoveFile(file.id, props.noteFiles, props.setNoteFiles, props.setLoading), href: "#", title: Translator.trans('card-pictures.label.remove') },
                    React.createElement("i", { className: "icon-delete" })))
            : React.createElement(React.Fragment, null,
                React.createElement("a", { href: fileUrl, target: "planFile" },
                    React.createElement("i", { className: "icon-clip" }),
                    file.fileName),
                React.createElement("a", { className: "file-remove", onClick: () => confirmRemoveFile(file.id, props.noteFiles, props.setNoteFiles, props.setLoading), href: "#", title: Translator.trans('card-pictures.label.remove') },
                    React.createElement("i", { className: "icon-delete" })))),
        React.createElement("div", { className: "file-edit-desc" },
            React.createElement("input", { name: `fileDescription[${file.id}]`, defaultValue: file === null || file === void 0 ? void 0 : file.description, className: "defInput", maxLength: 250, onChange: (event) => {
                    props.saveNoteFiles[file.id] = event.target.value;
                    props.setSaveNoteFiles(props.saveNoteFiles);
                } }))));
});
function updateNote(props) {
    props.setLoading(true);
    props.setErrorText('');
    return fetch(Routing.generate('aw_timeline_plan_update_note', { planId: props.segment.planId }), {
        method: 'post',
        body: JSON.stringify({
            note: props.editor.getData(),
            fileDescription: Object.entries(props.saveNoteFiles),
        })
    })
        .then(res => res.json())
        .then((result) => {
        if (result.status) {
            props.setNoteText(result.note);
            props.setNoteFiles(Object.values(result.files));
        }
        else if (result.error) {
            props.setErrorText(result.error);
        }
    })
        .finally(() => props.setLoading(false));
}
function uploadFile(files, segment, setNoteFiles, setAttachFileInfo, setLoading, setErrorText, tmpNoteFiles, setTmpNoteFiles) {
    return __awaiter(this, void 0, void 0, function* () {
        setLoading(true);
        setErrorText('');
        const input = document.getElementById('attachFile');
        const formData = new FormData();
        const file = files instanceof Array
            ? files[0]
            : input.files[0];
        formData.append('file', file);
        setAttachFileInfo(file.name + ' ' + Translator.trans('loading', {}, 'mobile-native'));
        let postUrl;
        if (isSegment) {
            let [type, itineraryId] = location.pathname.replace(/^\/+|\/+$/g, '').split('/');
            if (isAdding) {
                itineraryId = '-1';
            }
            postUrl = Routing.generate(ROUTE_SEGMENT_UPLOAD_FILE, { type, itineraryId });
        }
        else {
            postUrl = Routing.generate(ROUTE_PLAN_UPLOAD_FILE, { planId: segment.planId });
        }
        const result = (yield API.post(postUrl, formData)).data;
        if (result.status) {
            setNoteFiles(Object.values(result.files));
            if (result.tmpFiles) {
                setTmpNoteFiles([...tmpNoteFiles, ...Object.values(result.tmpFiles)]);
            }
        }
        else if (result.error) {
            setErrorText(result.error);
        }
        input.value = null;
        setAttachFileInfo('');
        setLoading(false);
    });
}
function confirmRemoveFile(fileId, noteFiles, setNoteFiles, setLoading) {
    const $obj = $(`<p>${Translator.trans('you-sure-delete-file', {}, 'trips')}</p>`);
    $($obj).dialog({
        title: Translator.trans('confirmation', {}, 'trips'),
        width: 400,
        height: 'auto',
        resizable: false,
        modal: true,
        buttons: [
            {
                'class': 'btn-silver',
                'text': Translator.trans('button.no'),
                'click': () => $($obj).dialog('destroy'),
            },
            {
                'class': 'btn-blue',
                'text': Translator.trans('button.yes'),
                'click': () => {
                    $($obj).dialog('destroy');
                    if (isNaN(fileId)) {
                        noteFiles.map((file, key) => {
                            if (file.id == fileId) {
                                delete noteFiles[key];
                            }
                        });
                        setNoteFiles(Object.values(noteFiles));
                    }
                    else {
                        setLoading(true);
                        requestRemoveFile(fileId, noteFiles, setNoteFiles);
                        setLoading(false);
                    }
                },
            },
        ]
    });
}
function requestRemoveFile(fileId, noteFiles, setNoteFiles) {
    return __awaiter(this, void 0, void 0, function* () {
        const fileRemoveUrl = isSegment
            ? Routing.generate('aw_timeline_itinerary_remove_file', { itineraryFileId: fileId })
            : Routing.generate('aw_timeline_plan_remove_file', { planFileId: fileId });
        const result = (yield API.post(fileRemoveUrl, {})).data;
        if (result.status) {
            noteFiles.map((file, key) => {
                if (file.id == fileId) {
                    delete noteFiles[key];
                }
            });
            setNoteFiles(Object.values(noteFiles));
        }
    });
}
function linkify(text) {
    let replacedText, replacePattern1, replacePattern2, replacePattern3;
    replacePattern1 = /(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/gim;
    replacedText = text.replace(replacePattern1, '<a href="$1" target="_blank">$1</a>');
    replacePattern2 = /(^|[^\/])(www\.[\S]+(\b|$))/gim;
    replacedText = replacedText.replace(replacePattern2, '$1<a href="http://$2" target="_blank">$2</a>');
    replacePattern3 = /(([a-zA-Z0-9\-\_\.])+@[a-zA-Z\_]+?(\.[a-zA-Z]{2,6})+)/gim;
    replacedText = replacedText.replace(replacePattern3, '<a href="mailto:$1">$1</a>');
    return replacedText;
}
export default _Notes;
//# sourceMappingURL=_Notes.js.map
