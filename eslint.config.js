import js from '@eslint/js';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import tseslint from 'typescript-eslint';

export default tseslint.config(
    {
        ignores: ['public/**', 'vendor/**', 'node_modules/**', 'bootstrap/ssr/**', 'storage/**', '.claude/**', 'docs/**'],
    },
    js.configs.recommended,
    ...tseslint.configs.strict,
    {
        files: ['resources/js/**/*.{ts,tsx}'],
        plugins: {
            react,
            'react-hooks': reactHooks,
        },
        languageOptions: {
            parserOptions: {
                ecmaFeatures: { jsx: true },
            },
        },
        settings: {
            react: { version: 'detect' },
        },
        rules: {
            ...react.configs.recommended.rules,
            ...react.configs['jsx-runtime'].rules,
            ...reactHooks.configs.recommended.rules,
            '@typescript-eslint/no-explicit-any': 'error',
            '@typescript-eslint/consistent-type-imports': 'error',
            // All interface text must come from lang/{ar,en}.json via useT() (L10N-001).
            'react/jsx-no-literals': ['error', { noStrings: true, ignoreProps: true, noAttributeStrings: false }],
            'react/prop-types': 'off',
        },
    },
);
