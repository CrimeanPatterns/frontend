//
//  GlobalConfig.m
//  Safari Extension Extension
//
//  Created by Aleksey Anikin on 17/07/2019.
//  Copyright © 2019 Aleksey Anikin. All rights reserved.
//

#import "GlobalConfig.h"

@implementation GlobalConfig {
    NSInteger tabId;
}
@synthesize openPorts;
@synthesize tabOwnerMap;

#pragma mark Singleton Methods

+ (id)sharedConfig {
    static GlobalConfig *sharedConfig = nil;
    static dispatch_once_t onceToken;
    dispatch_once(&onceToken, ^{
        sharedConfig = [[self alloc] init];
    });
    return sharedConfig;
}

-(NSString *)getNextTabId {
    tabId += 1;
    
    return [@(tabId) stringValue];
}

- (id)init {
    if (self = [super init]) {
        openPorts = [[NSMutableDictionary alloc] init];
        tabOwnerMap = [[NSMutableDictionary alloc] init];
        tabId = 100;
    }
    return self;
}

- (void)dealloc {
    
}

@end
