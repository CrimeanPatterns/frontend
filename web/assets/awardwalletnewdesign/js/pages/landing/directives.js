define(['angular-boot'], () => {
	angular
		.module('landingPage-dir', [])
		.directive('autofill', ['$location', '$timeout', ($location, $timeout) => {
			const setValue = (name, model, scope) => {
				const value = model.$viewValue;
				if (Object.prototype.hasOwnProperty.call($location.search(), name) && !value) {
					model.$setViewValue($location.search()[name]);
					model.$render();
					scope.$apply();
				}
			};
			return {
				restrict: 'A',
				require: 'ngModel',
				link: (scope, elem, attrs, ctrl) => {
					$timeout(() => {
						setValue(attrs.autofill, ctrl, scope);
					});
					scope.$on('$locationChangeSuccess', () => {
						setValue(attrs.autofill, ctrl, scope);
					});
				}
			}
		}])
		.directive('samePassword', [() => {
			return {
				require: 'ngModel',
				link: (scope, elem, attrs, ctrl) => {
					const originalPass = '#' + attrs.samePassword;

					elem.add(originalPass).on('keyup', () => {
						scope.$apply(() => {
							const originalPassValue = $(originalPass).val();
							const samePassValue = elem.val();
							const validity = originalPassValue === samePassValue || samePassValue === '' || originalPassValue === '';
							ctrl.$setValidity('samePassword', validity);
						});
					});
				}
			}
		}]);
});