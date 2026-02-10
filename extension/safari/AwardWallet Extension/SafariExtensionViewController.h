//
//  SafariExtensionViewController.h
//  AwardWallet Extension
//
//  Created by Anikin Aleksey on 22/07/2019.
//  Copyright © 2019 AwardWallet LLC. All rights reserved.
//

#import <SafariServices/SafariServices.h>

@interface SafariExtensionViewController : SFSafariExtensionViewController

+ (SafariExtensionViewController *)sharedController;

@end
