angular.module('AwardWalletMobile').service('Form', [
    function() {
        function parse(fields) {
            var data = {};
            angular.forEach(fields, function (field) {
                if (field.mapped) {
                    if (field.children) {
                        data[field.name] = parse(field.children);
                    } else if (field.type === 'choice') {
                        if (field.hasOwnProperty('selectedOption') && typeof(field.selectedOption) === 'object') {
                            data[field.name] = field.selectedOption.value;
                        } else {
                            data[field.name] = field.value;
                        }
                    } else if (!(field.type === 'passwordEdit' && field.changed !== true))
                        data[field.name] = field.value;
                }
            });
            return data;
        }

        return {
            parseData: parse
        };
    }
]);