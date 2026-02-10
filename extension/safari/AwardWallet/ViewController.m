//
//  ViewController.m
//  AwardWallet
//
//  Created by Anikin Aleksey on 22/07/2019.
//  Copyright © 2019 AwardWallet LLC. All rights reserved.
//

#import "ViewController.h"
#import <SafariServices/SFSafariApplication.h>

@implementation ViewController

- (void)viewDidLoad {
    [super viewDidLoad];
    self.appNameLabel.stringValue = @"AwardWallet";
}

- (IBAction)openSafariExtensionPreferences:(id)sender {
    [SFSafariApplication showPreferencesForExtensionWithIdentifier:@"com.awardwallet.macos.safari-extension" completionHandler:^(NSError * _Nullable error) {
        if (error) {
            // Insert code to inform the user something went wrong.
        }
    }];
}

@end
