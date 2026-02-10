const Encore = require("@symfony/webpack-encore");
const CopyWebpackPlugin = require("copy-webpack-plugin");
const ESLintPlugin = require("eslint-webpack-plugin");
const ForkTsCheckerWebpackPlugin = require("fork-ts-checker-webpack-plugin");
const path = require("path");
const fs = require("fs");
const webpack = require("webpack");

function path2BlockName(filename) {
  return filename
    .replace(/^.*\/?assets\/bem\/block\//g, "")
    .replace(/\//g, "-");
}

function hasIndexFile(directoryPath) {
  const indexFiles = ["index.js", "index.ts", "index.tsx"];

  for (let i = 0; i < indexFiles.length; i++) {
    const indexFilePath = path.join(directoryPath, indexFiles[i]);

    if (fs.existsSync(indexFilePath)) {
      return true;
    }
  }

  return false;
}

function getBlockName(filePath) {
  const directoryPath = path.dirname(filePath);
  const directoryComponents = directoryPath.split("/");
  let currentPath;
  let dirs = [];

  for (let i = directoryComponents.length - 1; i >= 0; i--) {
    currentPath = directoryComponents.slice(0, i + 1).join("/");

    if (hasIndexFile(currentPath)) {
      return (
        path2BlockName(currentPath) + (dirs.length ? "/" + dirs.join("/") : "")
      );
    }

    dirs.push(directoryComponents[i]);
  }

  throw new Error("Block name not found for " + filePath);
}

Encore
  // directory where compiled assets will be stored
  .setOutputPath("web/a/")
  // public path used by the web server to access the output path
  .setPublicPath((process.env.CDN_HOST || "") + "/a")
  .addPlugin(
    new CopyWebpackPlugin({
      patterns: [
        {
          from: "./assets/bem/block/**/*.{png,jpg,jpeg,gif,svg,ico,webp}",
          to: ({ absoluteFilename }) => {
            let filename = absoluteFilename;

            if (/^.*\/?assets\/bem\/block\//.test(filename)) {
              filename =
                "blocks/" +
                getBlockName(filename) +
                "/" +
                filename.replace(/.*\/([^\/]+)$/g, "$1");
              //console.info('deploy asset ' + filename);
            }

            // before ext add hash
            return filename.replace(/(\.[^.]+)$/, "[contenthash]$1");
          },
        },
      ],
    }),
  )
  .addPlugin(
    new webpack.NormalModuleReplacementPlugin(/^json!(.*)$/, function (
      resource,
    ) {
      resource.request = resource.request.replace(/^json!/, "");
    }),
  )
  .addPlugin(
    new ESLintPlugin({
      extensions: ["ts", "tsx", "js", "jsx"],
      lintDirtyModulesOnly: true,
      files: "./assets/**/*.(ts|tsx)",
    }),
  )
  .addPlugin(
    new ForkTsCheckerWebpackPlugin({
      typescript: {
        configFile: "./assets/tsconfig.json",
      },
    }),
  )
  .addPlugin(
    new webpack.DefinePlugin({
      __CHROME_EXTENSION_ID__: Encore.isProduction()
        ? "'elbkchakmaiinadjpnmdgpflpjogpgmb'"
        : "'nlfhklfcdielnbndncmdnibglgkfdfde'",
    }),
  )
  .configureImageRule({}, (loaderRule) => {
    loaderRule.test = /\.(png|jpg|jpeg|gif|ico|webp|avif)$/;
  })
  .addRule({
    test: /\.svg$/i,
    issuer: /\.[jt]sx?$/,
    resourceQuery: { not: [/url/, /save/] },
    use: [
      {
        loader: "@svgr/webpack",
        options: {
          removeViewBox: false,
          removeDimensions: true,
          dimensions: false,
        },
      },
    ],
  })
  .addRule({
    test: /\.svg$/i,
    issuer: /\.[jt]sx?$/,
    resourceQuery: /save/,
    use: [
      {
        loader: "@svgr/webpack",
        options: {
          removeViewBox: false,
          removeDimensions: false,
          dimensions: true,
        },
      },
    ],
  })
  .addRule({
    test: /\.svg$/i,
    type: "asset",
    resourceQuery: /url/,
  })

  /*
   * ENTRY CONFIG
   *
   * Add 1 entry for each "page" of your app
   * (including one that's included on every page - e.g. "app")
   *
   * Each entry will result in one JavaScript file (e.g. app.js)
   * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
   */

  // temporary, only for main layout. All styles must be imported inside the entry page, not layout
  .addStyleEntry("main", "./assets/entry-point-deprecated/main.js")

  .addStyleEntry(
    "palettes/learn-palette",
    "./assets/styles/palettes/learn-palette.scss",
  )

  .addEntry("landing", "./assets/entry-point-deprecated/landing.js")
  .addEntry(
    "landing-mobile",
    "./assets/entry-point-deprecated/landing-mobile.js",
  )
  .addEntry("account", "./assets/entry-point-deprecated/account.js")
  .addEntry("scanner", "./assets/entry-point-deprecated/scanner.js")
  .addEntry("trips", "./assets/entry-point-deprecated/trips.js")
  .addEntry(
    "new-timeline",
    "./assets/entry-point-deprecated/timeline/new-index.js",
  )
  .addEntry("timeline", "./assets/entry-point-deprecated/timeline/index.js")
  .addEntry("account-list", "./assets/entry-point-deprecated/account-list.js")
  .addEntry("api-doc", "./assets/entry-point-deprecated/api-doc.js")
  .addEntry("offer", "./assets/entry-point-deprecated/offer.js")
  .addEntry("one-card", "./assets/entry-point-deprecated/one-card.js")
  .addEntry("pressrelease", "./assets/entry-point-deprecated/pressrelease.js")
  .addEntry("form", "./assets/entry-point-deprecated/form.js")
  .addEntry("booking", "./assets/entry-point-deprecated/booking.js")
  .addEntry("manager", "./assets/entry-point-deprecated/manager.js")
  .addEntry(
    "hotel-reward",
    "./assets/entry-point-deprecated/hotel-reward/index.js",
  )
  .addEntry("iframe", "./assets/entry-point-deprecated/iframe.js")
  .addEntry(
    "flight-search",
    "./assets/js-deprecated/component-deprecated/FlightSearch/index.js",
  )
  .addEntry(
    "itinerary-edit",
    "./assets/js-deprecated/component-deprecated/form/ItineraryEdit.js",
  )
  .addEntry(
    "transaction-analyzer",
    "./assets/entry-point-deprecated/transaction-analyzer/index.js",
  )
  .addEntry("new-pages", "./assets/react-app/Page.index.tsx")
  .addEntry("user-settings", "./assets/react-app/UserSettings.index.tsx")

  .addEntry("logo", "./assets/entry-point-deprecated/logo.js")
  .addEntry(
    "user-short-info",
    "./assets/entry-point-deprecated/user-short-info.js",
  )
  .addEntry("email-invite", "./assets/entry-point-deprecated/email-invite.js")
  .addEntry(
    "person-activate",
    "./assets/entry-point-deprecated/person-activate.js",
  )
  .addEntry(
    "available-upgrades",
    "./assets/entry-point-deprecated/available-upgrades.js",
  )
  .addEntry(
    "button-accounts",
    "./assets/entry-point-deprecated/button-accounts.js",
  )
  .addEntry("main-layout", "./assets/entry-point-deprecated/main-layout.js")
  .addEntry(
    "need-two-factor",
    "./assets/entry-point-deprecated/need-two-factor.js",
  )
  .addEntry("offer-popup", "./assets/entry-point-deprecated/offer-popup.js")
  .addEntry(
    "awardwallet-plus-subscription-offer",
    "./assets/bem/block/awardwallet-plus-subscription-offer/index.ts",
  )
  .addEntry(
    "unauthorized-mailbox-popup",
    "./assets/entry-point-deprecated/unauthorized-mailbox-popup.js",
  )
  .addEntry(
    "on-account-expiration-import",
    "./assets/entry-point-deprecated/on-account-expirations-import.js",
  )
  .addEntry("booking-adv", "./assets/entry-point-deprecated/booking-adv.js")
  .addEntry("email-ndr", "./assets/entry-point-deprecated/email-ndr.js")
  .addEntry("beta-popup", "./assets/entry-point-deprecated/beta-popup.js")
  .addEntry(
    "main-menu-trip",
    "./assets/entry-point-deprecated/main-menu-trip.js",
  )
  .addEntry(
    "business-default",
    "./assets/entry-point-deprecated/business-default.js",
  )
  .addEntry("not-logged", "./assets/entry-point-deprecated/not-logged.js")
  .addEntry(
    "not-logged-nav-mobile",
    "./assets/entry-point-deprecated/not-logged-nav-mobile.js",
  )
  .addEntry("search-box", "./assets/entry-point-deprecated/search-box.js")
  .addEntry("user-mailbox", "./assets/entry-point-deprecated/user-mailbox.js")
  .addEntry("index-trip", "./assets/entry-point-deprecated/index-trip.js")
  .addEntry("print-layout", "./assets/entry-point-deprecated/print-layout.js")
  .addEntry(
    "fields-impersonated",
    "./assets/entry-point-deprecated/fields-impersonated.js",
  )
  .addEntry(
    "account-list-web-push",
    "./assets/entry-point-deprecated/account-list-web-push.js",
  )
  .addEntry(
    "fields-not-impersonated",
    "./assets/entry-point-deprecated/fields-not-impersonated.js",
  )
  .addEntry(
    "fields-itinerary",
    "./assets/entry-point-deprecated/fields-itinerary.js",
  )
  .addEntry(
    "fields-itinerary-address-autocomplete",
    "./assets/entry-point-deprecated/fields-itinerary-address-autocomplete.js",
  )
  .addEntry("flight", "./assets/entry-point-deprecated/flight.js")
  .addEntry("reservation", "./assets/entry-point-deprecated/reservation.js")
  .addEntry("rental", "./assets/entry-point-deprecated/rental.js")
  .addEntry("taxi-ride", "./assets/entry-point-deprecated/taxi-ride.js")
  .addEntry("bus-ride", "./assets/entry-point-deprecated/bus-ride.js")
  .addEntry("train-ride", "./assets/entry-point-deprecated/train-ride.js")
  .addEntry("ferry-ride", "./assets/entry-point-deprecated/ferry-ride.js")
  .addEntry("cruise", "./assets/entry-point-deprecated/cruise.js")
  .addEntry("event", "./assets/entry-point-deprecated/event.js")
  .addEntry("parking", "./assets/entry-point-deprecated/parking.js")
  .addEntry("global-loader", "./assets/bem/block/global-loader/index.ts")

  // will require an extra script tag for runtime.js
  // but, you probably want this, unless you're building a single-page app
  .enableSingleRuntimeChunk()
  .cleanupOutputBeforeBuild()
  .enableSourceMaps(!Encore.isProduction())
  .splitEntryChunks()
  .configureSplitChunks((splitChunks) => {
    splitChunks.cacheGroups.translator = {
      test: /[\\/]assets[\\/]common[\\/]js[\\/]translator/,
      name: "translator",
      chunks: "all",
      enforce: true,
    };
  })

  // enables hashed filenames (e.g. app.abc123.css)
  .enableVersioning(Encore.isProduction())
  .enableTypeScriptLoader()
  .enableSassLoader(function (options) {
    options.sassOptions = {
      includePaths: ["./assets/bem/block", "./assets/react-app"],
    };
  })
  .enableLessLoader(function (options) {
    options.lessOptions = {
      javascriptEnabled: true,
    };
  })
  .configureCssLoader((options) => {
    options.modules = {
      mode: "local",
      auto: /\.module\.scss$/i,
      localIdentName: "[local]_[hash:base64]",
    };
  })
  .configureMiniCssExtractPlugin(
    () => {},
    (pluginOptions) => {
      pluginOptions.ignoreOrder = true;
    },
  )
  .enablePostCssLoader()
  .autoProvidejQuery()

  .addAliases({
    webpack: path.resolve(__dirname, "./assets"),
    "webpack-ts": path.resolve(__dirname, "./assets/bem/ts"),
    "angular-boot": path.resolve(
      __dirname,
      "./assets/bem/ts/shim/angular-boot",
    ),
    "translator-boot": path.resolve(
      __dirname,
      "./assets/bem/ts/service/translator",
    ),
    router: path.resolve(__dirname, "./assets/bem/ts/service/router"),
    routing: path.resolve(__dirname, "./assets/bem/ts/service/router"),

    awardwallet: path.resolve(__dirname, "web/design/awardWallet"),
    browserext: path.resolve(__dirname, "web/kernel/browserExt"),
    "forge-api-awardwallet": path.resolve(
      __dirname,
      "web/extension/forge-api-awardwallet",
    ),
    "extension-main": path.resolve(__dirname, "web/extension/main"),
    "extension-boot": path.resolve(
      __dirname,
      "web/assets/common/js/extension-boot",
    ),
    "reactjs-boot": path.resolve(
      __dirname,
      "web/assets/common/js/reactjs-boot",
    ),
    text: path.resolve(
      __dirname,
      "web/assets/common/vendors/requirejs-text/text",
    ),
    jsx: path.resolve(__dirname, "node_modules/jsx-requirejs-plugin/js/jsx"),
    json: path.resolve(__dirname, "node_modules/requirejs-plugins/src/json"),
    "intl-path": path.resolve(__dirname, "node_modules/intl"),
    JSXTransformer: path.resolve(
      __dirname,
      "node_modules/jsx-requirejs-plugin/js/JSXTransformer",
    ),
    common: path.resolve(__dirname, "web/assets/common/js/"),
    controllers: path.resolve(
      __dirname,
      "web/assets/awardwalletnewdesign/js/controllers",
    ),
    cookie: path.resolve(
      __dirname,
      "node_modules/jquery.cookie/jquery.cookie.js",
    ),
    directives: path.resolve(
      __dirname,
      "web/assets/awardwalletnewdesign/js/directives",
    ),
    domReady: path.resolve(__dirname, "web/assets/common/js/domReady"),
    filters: path.resolve(
      __dirname,
      "web/assets/awardwalletnewdesign/js/filters",
    ),
    forms: path.resolve(
      __dirname,
      "web/assets/awardwalletnewdesign/js/directives/forms",
    ),
    "jquery-boot": path.resolve(__dirname, "web/assets/common/js/jquery-boot"),
    jqueryui: path.resolve(
      __dirname,
      "web/assets/common/vendors/jquery-ui/jquery-ui.min",
    ),
    "jqueryui-ui": path.resolve(
      __dirname,
      "node_modules/components-jqueryui/ui",
    ),
    lib: path.resolve(__dirname, "web/assets/awardwalletnewdesign/js/lib"),
    libscripts: path.resolve(__dirname, "web/lib/scripts"),
    "extension-callback-manager": path.resolve(
      __dirname,
      "web/extension/CallbackManager",
    ),
    "extension-communicator": path.resolve(
      __dirname,
      "web/extension/ExtensionCommunicator",
    ),
    oldscripts: path.resolve(__dirname, "web/assets/common/js/oldScripts"),
    pages: path.resolve(__dirname, "web/assets/awardwalletnewdesign/js/pages"),
    services: path.resolve(
      __dirname,
      "web/assets/awardwalletnewdesign/js/services",
    ),
    "touch-punch": path.resolve(
      __dirname,
      "node_modules/jquery-ui-touch-punch/jquery.ui.touch-punch",
    ),
    vendor: path.resolve(__dirname, "web/assets/common/vendors"),
    dateTimeDiff: path.resolve(__dirname, "web/assets/common/js/dateTimeDiff"),
    sockjs: path.resolve(__dirname, "node_modules/sockjs-client/dist/sockjs"),
    "jquery-slim": path.resolve(
      __dirname,
      "web/assets/awardwalletnewdesign/js/lib/slim.jquery.min",
    ),
    lunr: path.resolve(__dirname, "web/assets/common/js/lunr"),
    lunr_stemmer: path.resolve(
      __dirname,
      "web/assets/common/js/lunr/lunr.stemmer.support.min",
    ),
    lunr_es: path.resolve(__dirname, "web/assets/common/js/lunr/lunr.es.min"),
    lunr_fr: path.resolve(__dirname, "web/assets/common/js/lunr/lunr.fr.min"),
    lunr_pt: path.resolve(__dirname, "web/assets/common/js/lunr/lunr.pt.min"),
    lunr_ru: path.resolve(__dirname, "web/assets/common/js/lunr/lunr.ru.min"),
    lunr_de: path.resolve(__dirname, "web/assets/common/js/lunr/lunr.de.min"),
    lunr_multi: path.resolve(
      __dirname,
      "web/assets/common/js/lunr/lunr.multi.min",
    ),
    chartjs: path.resolve(__dirname, "node_modules/chart.js/dist/Chart.bundle"),
    "chartjs-plugin-watermark": path.resolve(
      __dirname,
      "web/assets/common/js/chartjs-plugin-watermark.min",
    ),
    "chartjs-plugin-datalabels": path.resolve(
      __dirname,
      "web/assets/common/js/chartjs-plugin-datalabels",
    ),
    tipjs: path.resolve(__dirname, "web/assets/common/js/intro.min"),
    cldr: path.resolve(__dirname, "node_modules/cldrjs/dist/cldr"),
    globalize: path.resolve(__dirname, "node_modules/globalize/dist/globalize"),
    "date-time-diff": path.resolve(
      __dirname,
      "node_modules/@awardwallet/date-time-diff-requirejs/lib/date-time-diff.min",
    ),
    trcking: path.resolve(__dirname, "web/assets/common/js/trcking"),

    "extension-client/bundle": path.resolve(
      __dirname,
      "web/assets/extension-client/bundle",
    ),

    "/images": path.resolve(__dirname, "web/images"),
    "/lib": path.resolve(__dirname, "web/lib"),
    "/design": path.resolve(__dirname, "web/design"),
    "/assets": path.resolve(__dirname, "web/assets"),
    "@UI": path.resolve(__dirname, "assets/react-app/UI"),
    "@Utilities": path.resolve(__dirname, "assets/react-app/Utilities"),
    "@Bem": path.resolve(__dirname, "assets/bem"),
    "@Root": path.resolve(__dirname, "assets/react-app"),
    "@Services": path.resolve(__dirname, "assets/react-app/Services"),
  });

if (process.env.CDN_PATH) {
  Encore.setManifestKeyPrefix(process.env.CDN_PATH.substr(1));
}

const glob = require("glob");

// scan index.js files in subdirs in ./assets/bem/block/page and add them as entries
const blocks = [
  ...glob.sync("./assets/bem/block/page/**/index.{js,ts,jsx,tsx}"),
  ...glob.sync("./assets/bem/block/page/**/*.entry.{js,ts,jsx,tsx}"),
];

for (const index in blocks) {
  const file = blocks[index];
  let blockName = getBlockName(file);

  const matches = file.match(/([\w\\.]+)\.entry\.\w+/);

  if (matches) {
    blockName = blockName + "/" + matches[1];
  }

  // console.info('adding entry "' + blockName + '" from ' + file);
  Encore.addEntry(blockName, file);
}

const offers = glob.sync("./assets/entry-point-deprecated/offers/*.js");

for (index in offers) {
  let file = offers[index];
  Encore.addEntry("offers/" + path.basename(file, ".js"), file);
}

for (let lang of ["de", "en", "es", "fr", "pt", "ru", "zh_CN", "zh_TW"]) {
  Encore.addEntry("trans/" + lang, "./assets/bem/translations/" + lang + ".js");
}

Encore.enableBuildCache({ config: [__filename] });

// const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;
// Encore.addPlugin(new BundleAnalyzerPlugin());

config = Encore.getWebpackConfig();
// config.optimization.minimize = false;
// console.log("[CONFIG]", config);

module.exports = config;
