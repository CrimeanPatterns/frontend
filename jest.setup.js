import "@testing-library/jest-dom";
import "jest-location-mock";
import "./assets/bem/translations/en";
import "./web/assets/translations/en";
import "./assets/react-app/Tests/__mocks__/resizeObserver";

jest.mock("./assets/bem/ts/service/errorDialog", () => ({
  __esModule: true,
  default: jest.fn(),
}));

class IntersectionObserverMock {
  constructor(callback, options) {
    this.callback = callback;
    this.options = options;
    this.targets = new Set();
  }

  observe(target) {
    this.targets.add(target);
    this.callback(this.collectEntries());
  }

  unobserve(target) {
    this.targets.delete(target);
  }

  disconnect() {
    this.targets.clear();
  }

  collectEntries() {
    const entries = [];
    this.targets.forEach((target) => {
      const entry = {
        target,
        isIntersecting: true, // Adjust this based on your test scenario
        intersectionRatio: 1, // Adjust this based on your test scenario
        intersectionRect: {
          top: 0,
          bottom: 100,
          left: 0,
          right: 100,
          height: 100,
          width: 100,
        },
        boundingClientRect: {
          top: 0,
          bottom: 100,
          left: 0,
          right: 100,
          height: 100,
          width: 100,
        },
        rootBounds: null,
        time: 100,
      };
      entries.push(entry);
    });
    return entries;
  }
}
global.IntersectionObserver = IntersectionObserverMock;
