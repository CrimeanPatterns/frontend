const globals = require("globals");
const tsParser = require("@typescript-eslint/parser");
const tsEslintPlugin = require("@typescript-eslint/eslint-plugin");
const sortImportsPlugin = require("eslint-plugin-sort-imports-es6-autofix");
const prettierPlugin = require("eslint-plugin-prettier");
const jestPlugin = require("eslint-plugin-jest");
const esLint = require("@eslint/js");
const tseslint = require("typescript-eslint");
const reactRecommended = require("eslint-plugin-react/configs/recommended");
const reactHooks = require("eslint-plugin-react-hooks");
const eslintConfigPrettier = require("eslint-config-prettier");

module.exports = [
  {
    name: "eslint assets config",
  },
  {
    files: ["*.ts", "*.tsx"],
  },
  esLint.configs.recommended,
  ...tseslint.configs.strictTypeChecked,
  reactRecommended,
  eslintConfigPrettier,
  {
    languageOptions: {
      ecmaVersion: 2021,
      sourceType: "module",
      globals: {
        ...globals.browser,
      },
      parser: tsParser,
      parserOptions: {
        sourceType: "module",
        project: "./tsconfig.json",
        tsconfigRootDir: __dirname + "/assets",
        ecmaFeatures: { jsx: true },
      },
    },
  },
  {
    plugins: {
      tsEslintPlugin,
      sortImportsPlugin,
      prettierPlugin,
      jestPlugin,
      reactHooks,
    },
  },

  {
    rules: {
      "no-console": 1,
      "linebreak-style": ["error", "unix"],
      "sort-imports": [
        "error",
        {
          ignoreCase: false,
          ignoreDeclarationSort: true,
          ignoreMemberSort: false,
          memberSyntaxSortOrder: ["none", "all", "multiple", "single"],
        },
      ],
      "sortImportsPlugin/sort-imports-es6": [
        2,
        {
          ignoreCase: false,
          ignoreMemberSort: false,
          memberSyntaxSortOrder: ["none", "all", "multiple", "single"],
        },
      ],
      "reactHooks/exhaustive-deps": "off",
      "@typescript-eslint/no-misused-promises": [
        2,
        {
          checksVoidReturn: {
            attributes: false,
          },
        },
      ],
      "no-duplicate-imports": "error",
      "@typescript-eslint/no-dynamic-delete": "off",
      "react/jsx-curly-brace-presence": ["error", { props: "never" }],
      "react/prop-types": "off",
      "reactHooks/rules-of-hooks": "error",
    },
  },
  {
    settings: {
      react: {
        version: "detect",
      },
    },
  },
  {
    ignores: [
      "eslint.config.js",
      "**/*.js",
      "assets/js-deprecated",
      "**/node_modules/",
    ],
  },
];
