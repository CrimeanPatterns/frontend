import _ from 'lodash';
// noinspection NpmUsedModulesInstalled
import legacyErrorDialog from 'lib/errorDialog';

export default function errorDialog(error, disablePopup) {
    legacyErrorDialog(
        {
            status: _.get(error, 'response.status', 0),
            data: _.get(error, 'response.data'),
            config: {
                method: _.get(error, 'config.method'),
                url: _.get(error, 'config.url'),
                data: _.get(error, 'config.data'),
            },
        },
        disablePopup,
    );
}
