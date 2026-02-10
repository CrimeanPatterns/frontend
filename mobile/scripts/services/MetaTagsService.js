function MetaTagsService() {
    const service = this;
    let defaultTags = {};
    const tagElements = [];

    function setDefaultTags(tags) {
        defaultTags = {...tags};
        setTags({});
    }

    function setTags(tags) {
        clearTags();
        tags = {...defaultTags, ...tags};
        for (const tagName in tags) {
            if (!Object.prototype.hasOwnProperty.call(tags, tagName)) {
                continue;
            }
            const tagContent = tags[tagName];
            const tagElement = getTagElement(tagContent, tagName);
            document.head.appendChild(tagElement);
            tagElements.push(tagElement);
        }
    }

    function clearTags() {
        for (const tagElement of tagElements) {
            document.head.removeChild(tagElement);
        }
        tagElements.length = 0;
    }

    function getTagElement(content, name) {
        if (name === 'title') {
            const title = document.createElement('title');
            title.textContent = content;
            return title;
        } else if (name === 'canonical') {
            const link = document.createElement('link');
            link.setAttribute('rel', 'canonical');
            link.setAttribute('href', content);
            return link;
        } else {
            const meta = document.createElement('meta');
            meta.setAttribute('name', name);
            meta.setAttribute('content', content);
            return meta;
        }
    }

    service.setDefaultTags = setDefaultTags;
    service.setTags = setTags;
}

angular
    .module('AwardWalletMobile')
    .service('MetaTagsService', MetaTagsService);