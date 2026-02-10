export declare namespace Translator {
    interface IParams {
        [key: string]: string|number
    }
    interface ITranslator {
        locale: string;
        fallback: string;
        placeHolderPrefix: string;
        placeHolderSuffix: string;
        defaultDomain: string;
        pluralSeparator: string;
        add: (id: string, message: string, domain: string, locale: string) => ITranslator;
        trans: (id: string, parameters?: IParams, domain?: string, locale?: string) => string;
        transChoice: (id: string, number: number, parameters?: IParams, domain?: string, locale?: string) => string;
        reset: () => void;
        addMark: (id: string, domain: string, mess: string) => void;
        fromJSON: (data: string) => ITranslator;
    }
}

export declare namespace Routing {
    interface IRoutingData {
        base_url: string;
        routes: {[key: string]: unknown;}
        host: string;
        port: string;
        scheme: string;
        locale: string;
    }
    interface IRouter {
        setRoutingData: (routes: IRoutingData) => void;
        generate: (name: string, parameters?: {[key: string]: string | number;}) => string;
    }
}

declare global {
    interface Window {
        Translator: Translator.ITranslator;
        Routing: Routing.IRouter;
        inviteEmail: string;
        firstName: string;
        lastName: string;
        inviteCode: string;
        extensionV3info: import("@awardwallet/extension-client/dist/ExtensionInfo").ExtensionInfo
    }
}