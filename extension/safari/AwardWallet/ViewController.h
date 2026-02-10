//
//  ViewController.h
//  AwardWallet
//
//  Created by Anikin Aleksey on 22/07/2019.
//  Copyright © 2019 AwardWallet LLC. All rights reserved.
//

#import <Cocoa/Cocoa.h>

@interface ViewController : NSViewController

@property (weak, nonatomic) IBOutlet NSTextField * appNameLabel;

- (IBAction)openSafariExtensionPreferences:(id)sender;

@end

