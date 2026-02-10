//
//  GlobalConfig.h
//  Safari Extension Extension
//
//  Created by Aleksey Anikin on 17/07/2019.
//  Copyright © 2019 Aleksey Anikin. All rights reserved.
//

#import <Foundation/Foundation.h>
#import <SafariServices/SafariServices.h>

NS_ASSUME_NONNULL_BEGIN

@interface GlobalConfig : NSObject
{
    NSMutableDictionary *openPorts;
    NSMutableDictionary *tabOwnerMap;
}

@property (nonatomic, retain) NSMutableDictionary *openPorts;
@property (nonatomic, retain) NSMutableDictionary *tabOwnerMap;

+ (id)sharedConfig;
- (NSString *)getNextTabId;

@end

NS_ASSUME_NONNULL_END
