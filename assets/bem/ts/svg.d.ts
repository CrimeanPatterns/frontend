declare module '*.svg' {
    const Svg: React.FunctionComponent<React.SVGAttributes<SVGElement>>;

    export = Svg;
}

declare module '*.svg?url' {
    const content: string;
    export = content;
}

declare module '*.svg?save' {
    const content: React.FunctionComponent<React.SVGAttributes<SVGElement>>;
    export default content;
}
