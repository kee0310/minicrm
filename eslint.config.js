import js from '@eslint/js';
import prettier from 'eslint-config-prettier/flat';
import importPlugin from 'eslint-plugin-import';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';
import typescript from 'typescript-eslint';

/** @type {import('eslint').Linter.Config[]} */
export default [
    // Ignore patterns - must be first
    {
        ignores: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'app/**',
            'database/**',
            'config/**',
            'routes/**',
            'storage/**',
            'tests/**',
            'htdocs/**',
            'dist/**',
            'build/**',
            '*.config.js',
            '*.config.ts',
        ],
    },

    // JavaScript/TypeScript base config
    {
        files: ['**/*.{js,jsx,ts,tsx}'],
        languageOptions: {
            ecmaVersion: 2024,
            sourceType: 'module',
            globals: {
                ...globals.browser,
            },
        },
        rules: {
            ...js.configs.recommended.rules,
        },
    },

    // TypeScript specific config
    {
        files: ['**/*.{ts,tsx}'],
        languageOptions: {
            parser: typescript.parser,
            parserOptions: {
                ecmaVersion: 2024,
                sourceType: 'module',
            },
            globals: {
                ...globals.browser,
            },
        },
        plugins: {
            '@typescript-eslint': typescript.plugin,
        },
        rules: {
            ...typescript.configs.recommended.rules,
            '@typescript-eslint/no-unused-vars': [
                'error',
                {
                    argsIgnorePattern:
                        '^_|^props$|^params$|^options$|^event$|^handler$|^value$|^item$|^form$|^status$|^overrides$|^urlToCheck$|^currentUrl$|^ifTrue$|^ifFalse$|^mode$',
                    varsIgnorePattern: '^_',
                    caughtErrorsIgnorePattern: '^_',
                },
            ],
            'no-unused-vars': 'off',
        },
    },

    // React hooks config
    {
        files: ['**/*.{jsx,tsx}'],
        plugins: {
            'react-hooks': reactHooks,
        },
        rules: {
            ...reactHooks.configs.recommended.rules,
        },
    },

    // React config
    {
        files: ['**/*.{jsx,tsx}'],
        languageOptions: {
            parserOptions: {
                ecmaFeatures: {
                    jsx: true,
                },
            },
            globals: {
                ...globals.browser,
                React: 'readonly', // For JSX runtime
            },
        },
        plugins: {
            react,
        },
        settings: {
            react: {
                version: 'detect',
            },
            'import/react-version': 'detect',
        },
        rules: {
            ...react.configs.flat.recommended.rules,
            ...react.configs.flat['jsx-runtime'].rules,
            'react/react-in-jsx-scope': 'off',
            'react/prop-types': 'off',
            'react/no-unescaped-entities': 'off',
            'react/display-name': 'warn',
            'react/jsx-no-target-blank': [
                'error',
                {
                    enforceDynamicLinks: 'always',
                    warnOnSpreadAttributes: true,
                },
            ],
        },
    },

    // Import plugin config
    {
        files: ['**/*.{js,jsx,ts,tsx}'],
        plugins: {
            import: importPlugin,
        },
        settings: {
            'import/resolver': {
                typescript: true,
                node: true,
            },
        },
        rules: {
            ...importPlugin.flatConfigs.recommended.rules,
            'import/order': [
                'error',
                {
                    groups: [
                        'builtin',
                        'external',
                        'internal',
                        'parent',
                        'sibling',
                        'index',
                    ],
                    alphabetize: {
                        order: 'asc',
                        caseInsensitive: true,
                    },
                },
            ],
        },
    },

    // Prettier config (must be last)
    prettier,
];
