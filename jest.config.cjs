/** @type {import('ts-jest').JestConfigWithTsJest} */
module.exports = {
  preset: "ts-jest",
  testEnvironment: "jsdom",
  testMatch: ["<rootDir>/assets/**/*.test.*"],
  moduleFileExtensions: ["ts", "tsx", "js", "jsx", "json", "node"],
  transform: {
    "^.+\\.[tj]sx?$": [
      "ts-jest",
      {
        tsconfig: "./assets/tsconfig.json",
      },
    ],
  },
  moduleNameMapper: {
    "\\.(css|scss)$": "identity-obj-proxy",
    "^@Services/(.*)$": "<rootDir>/assets/react-app/Services/$1",
    "^@UI/(.*)$": "<rootDir>assets/react-app/UI/$1",
    "^@Root/(.*)$": "<rootDir>assets/react-app/$1",
    "^@Utilities/(.*)$": "<rootDir>assets/react-app/Utilities/$1",
    "^@Bem/(.*)$": "<rootDir>assets/bem/$1",
    "^.+.(png|jpg|ttf|woff|woff2)$": "jest-transform-stub",
    "\\.svg$": "<rootDir>/assets/react-app/Tests/__mocks__/svg.tsx",
  },
  moduleDirectories: ["node_modules", "<rootDir>/assets"],
  setupFilesAfterEnv: ["./jest.setup.js"],
  transformIgnorePatterns: ["/node_modules/(?!nanoid|is-retry-allowed)"],
};
