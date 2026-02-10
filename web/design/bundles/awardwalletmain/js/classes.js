function addClass(o, c){
    var re = new RegExp("(^|\\s)" + c + "(\\s|$)", "g")
    if (re.test(o.className)) return
    o.className = (o.className + " " + c).replace(/\s+/g, " ").replace(/(^ | $)/g, "")
}

function removeClass(o, c){
    var re = new RegExp("(^|\\s)" + c + "(\\s|$)", "g")
    o.className = o.className.replace(re, "$1").replace(/\s+/g, " ").replace(/(^ | $)/g, "")
}
function hasClass(o, c){
    var re = new RegExp("(^|\\s)" + c + "(\\s|$)", "g")
    if (re.test(o.className)) return true;
    return false;
}
function toggleClass(o, c){
    var re = new RegExp("(^|\\s)" + c + "(\\s|$)", "g")
    if (re.test(o.className)) {
        o.className = o.className.replace(re, "$1").replace(/\s+/g, " ").replace(/(^ | $)/g, "");
        return false;
    }else{
        o.className = (o.className + " " + c).replace(/\s+/g, " ").replace(/(^ | $)/g, "");
        return true;
    }
}

function getElementsByClass(getClass,e){
    e=e || document;
    if(e.querySelectorAll) {
        return e.querySelectorAll("." + getClass);
    }
    else if(e.getElementsByClassName) {
        return e.getElementsByClassName(getClass);
    }
    else {
        var list = e.getElementsByTagName('*'), i = list.length,
            classArray = getClass.split(/\s+/), result = [];
        while(i--) {
            if(list[i].className.search('\\b' + classArray + '\\b') != -1) {
                result.push(list[i]);
            }
        }
        return result;
    }
};
// inspired by @techpriester: https://gist.github.com/988627

function cssSelect(
    a,                         // take a simple selector like "name", "#name", or ".name", and
    b                          // an optional context, and
    ){
    a = a.match(/^([.#]?)([\w-]+)/); // split the selector into name and symbol.
    return(                    // return an element or list, from within the scope of
        b                        // the passed context
            || document              // or document,
        )[
        "getElement" + (         // obtained by the appropriate method calculated by
            a[1]
                ? a[1] == "#"
                ? "ById"           // the node by ID,
                : "sByClassName"   // the nodes by class name, or
                : "sByTagName"       // the nodes by tag name,
            )
        ](
            a[2]                     // called with the name.
        )
}
function DOMWalk(
    a, // a function to filter the node results (optional)
    b, // the ancestor element to walk (optional, defaults to document)
    c  // placeholder for the result node list
    ){
    b === c &&       // if b is undefined,
    (b=document);    // use the document

    c = c || [];     // intialize the node list if it doesn't exist

    if (b) {         // if the element exists
        if (           // and
            !a || a(b)   // matches the filter function if one exists,
            ) c.push(b);   // push it to the results.

        DOMWalk(a, b.firstChild, c); // repeat for the first child
        DOMWalk(a, b.nextSibling, c) // repeat for the next sibling
    }

    return c // return the results
}