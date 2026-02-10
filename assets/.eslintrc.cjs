module.exports = {
    root: true,
    env: {
        browser: true,
        es2021: true,
    },
    parser: '@typescript-eslint/parser',
    extends: [
        'eslint:recommended',
        'plugin:@typescript-eslint/strict-type-checked',
        'plugin:react-hooks/recommended',
        'plugin:react/recommended',
        'prettier',
    ],
    parserOptions: {
        sourceType: 'module',
        project: './tsconfig.json',
        tsconfigRootDir: __dirname,
        ecmaFeatures: { jsx: true },
    },
    settings: {
        react: {
            version: 'detect',
        },
    },
    plugins: ['@typescript-eslint', 'sort-imports-es6-autofix', 'prettier', 'jest'],
    rules: {
        'no-console': 1,
        'linebreak-style': ['error', 'unix'],
        'sort-imports': [
            'error',
            {
                ignoreCase: false,
                ignoreDeclarationSort: true,
                ignoreMemberSort: false,
                memberSyntaxSortOrder: ['none', 'all', 'multiple', 'single'],
            },
        ],
        'sort-imports-es6-autofix/sort-imports-es6': [
            2,
            {
                ignoreCase: false,
                ignoreMemberSort: false,
                memberSyntaxSortOrder: ['none', 'all', 'multiple', 'single'],
            },
        ],
        'react-hooks/exhaustive-deps': 'off',
        '@typescript-eslint/no-misused-promises': [
            2,
            {
                checksVoidReturn: {
                    attributes: false,
                },
            },
        ],
        'no-duplicate-imports': 'error',
        '@typescript-eslint/no-dynamic-delete': 'off',
        'react/jsx-curly-brace-presence': ['error', { props: 'never' }],
        'react/prop-types': 'off',
        '@typescript-eslint/restrict-template-expressions': 'off',
    },
    ignorePatterns: ['.eslintrc.cjs', '**/*.js', 'js-deprecated', 'bem'],
};
