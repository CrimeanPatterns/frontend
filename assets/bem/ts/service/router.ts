import { Routing } from '../global';
import Router from '../../../../web/bundles/fosjsrouting/js/router';
import routes from '../../../../web/js/routes.json';

const RouterService = Router as Routing.IRouter;
const RouterData = routes as Routing.IRoutingData;
RouterService.setRoutingData(RouterData);

// global variable for legacy code only
window.Routing = RouterService;
export default RouterService;
