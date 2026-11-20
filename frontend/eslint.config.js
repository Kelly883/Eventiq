import js from "@eslint/js";
import globals from "globals";
import react from "eslint-plugin-react";
import reactHooks from "eslint-plugin-react-hooks";
import reactRefresh from "eslint-plugin-react-refresh";
import tsParser from "@typescript-eslint/parser";

export default [
  {
    ignores: [
      "dist",
      "node_modules",
      "public/mockServiceWorker.js",
      "src/features/*/types/**",
    ],
  },
  {
    // Service workers
    files: ["public/firebase-messaging-sw.js"],
    languageOptions: {
      ecmaVersion: 2020,
      globals: {
        ...globals.serviceworker,
        firebase: "readonly",
      },
    },
    rules: {
      ...js.configs.recommended.rules,
    },
  },
  {
    // Build / config files
    files: ["vite.config.js", "*.config.js", "playwright.config.ts", "scripts/*.ts", "src/env.ts", "tests/*.spec.ts"],
    languageOptions: {
      ecmaVersion: 2020,
      globals: {
        ...globals.node,
      },
    },
    rules: {
      ...js.configs.recommended.rules,
    },
  },
  {
    // Test files - vitest globals
    files: ["**/*.test.{ts,tsx,js,jsx}", "**/*.spec.{ts,tsx,js,jsx}", "tests/**/*.{ts,js,tsx,jsx}"],
    languageOptions: {
      ecmaVersion: 2020,
      globals: {
        ...globals.browser,
        ...globals.node,
        describe: "readonly",
        it: "readonly",
        expect: "readonly",
        beforeAll: "readonly",
        afterEach: "readonly",
        afterAll: "readonly",
        beforeEach: "readonly",
        jest: "readonly",
        global: "readonly",
      },
      parser: tsParser,
      parserOptions: {
        ecmaVersion: "latest",
        ecmaFeatures: { jsx: true },
        sourceType: "module",
      },
    },
    plugins: {
      react,
      "react-hooks": reactHooks,
      "react-refresh": reactRefresh,
    },
    rules: {
      ...js.configs.recommended.rules,
      ...react.configs.recommended.rules,
      ...react.configs["jsx-runtime"].rules,
      ...reactHooks.configs.recommended.rules,
      "react-refresh/only-export-components": "off",
      "react/prop-types": "off",
      "no-unused-vars": "off",
      "react/no-unescaped-entities": "off",
      "no-empty": "off",
      "react-hooks/rules-of-hooks": "off",
      "no-case-declarations": "off",
      "no-undef": "off",
    },
  },
  {
    // JS/JSX files
    files: ["**/*.{js,jsx}"],
    ignores: ["public/firebase-messaging-sw.js"],
    languageOptions: {
      ecmaVersion: 2020,
      globals: globals.browser,
      parser: tsParser,
      parserOptions: {
        ecmaVersion: "latest",
        ecmaFeatures: { jsx: true },
        sourceType: "module",
      },
    },
    settings: {
      react: {
        version: "detect",
      },
    },
    plugins: {
      react,
      "react-hooks": reactHooks,
      "react-refresh": reactRefresh,
    },
    rules: {
      ...js.configs.recommended.rules,
      ...react.configs.recommended.rules,
      ...react.configs["jsx-runtime"].rules,
      ...reactHooks.configs.recommended.rules,
      "react-refresh/only-export-components": "off",
      "react/prop-types": "off",
      "no-unused-vars": "off",
      "react/no-unescaped-entities": "off",
      "no-empty": "off",
      "react-hooks/rules-of-hooks": "off",
      "no-case-declarations": "off",
    },
  },
  {
    // TS/TSX files
    files: ["**/*.{ts,tsx}"],
    languageOptions: {
      ecmaVersion: 2020,
      globals: globals.browser,
      parser: tsParser,
      parserOptions: {
        ecmaVersion: "latest",
        ecmaFeatures: { jsx: true },
        sourceType: "module",
      },
    },
    settings: {
      react: {
        version: "detect",
      },
    },
    plugins: {
      react,
      "react-hooks": reactHooks,
      "react-refresh": reactRefresh,
    },
    rules: {
      ...js.configs.recommended.rules,
      ...react.configs.recommended.rules,
      ...react.configs["jsx-runtime"].rules,
      ...reactHooks.configs.recommended.rules,
      "react-refresh/only-export-components": "off",
      "react/prop-types": "off",
      "no-unused-vars": "off",
      "react/no-unescaped-entities": "off",
      "no-empty": "off",
      "react-hooks/rules-of-hooks": "off",
      "no-case-declarations": "off",
    },
  },
];