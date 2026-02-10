const esprima = require('esprima'),
    babel = require('@babel/core'),
    fs = require('fs'),
    xml2js = require('xml2js'),
    http = require('http');

let timeout = null;
function setProcessTimeout(interval) {
    clearTimeout(timeout);
    timeout = setTimeout(function() { process.exit(0);}, interval * 1000)
}

setProcessTimeout(15);

const builder = new xml2js.Builder();

http.createServer(function (req, res) {
    let buffer = '';
    let chunkN = 0;
    setProcessTimeout(15);
    req.on('data', function (data) {
        buffer += data.toString();
        console.log(data.length + ' bytes received in chunk ' + chunkN++);
    });

    req.on('end', function () {
        console.log(buffer.length + ' bytes received overall');
        res.setHeader('Content-type', 'application/json');

        babel.transformAsync(
            buffer,
            {
                plugins: [
                    'babel-plugin-transform-class-properties'
                ],
                presets: [
                    '@babel/preset-env',
                    '@babel/preset-react',
                ],
                ast: false,
            }
        ).then(result => {
            let ast;
            try {
                ast = esprima.parseScript(result.code, {comment: true, loc: true, attachComment: true});
            } catch (e) {
                res.end(JSON.stringify({jserror: e.message}));
                console.log('Parser error: "' + e.message + '"');
                return;
            }
            try {
                const astXml = builder.buildObject(ast).toString();
                console.log('Writing reponse.');
                res.end(JSON.stringify({success: true, xml: astXml}));
            } catch (e) {
                res.end(JSON.stringify({xmlerror: e.message}));
                console.log('XML converter error: "' + e.message + '"');
                return;
            }
        }).catch(err => {
            res.end(JSON.stringify({babelerror: JSON.stringify(err)}));
            console.log('Babel error: ' + err);
        });
    });
}).listen(31337, '127.0.0.1', function () {
    console.log('server bound');
});

