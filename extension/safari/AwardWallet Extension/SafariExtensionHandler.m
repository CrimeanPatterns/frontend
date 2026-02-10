//
//  SafariExtensionHandler.m
//  Safari Extension Extension
//
//  Created by Aleksey Anikin on 16/07/2019.
//  Copyright © 2019 Aleksey Anikin. All rights reserved.
//

#import "SafariExtensionHandler.h"
#import "SafariExtensionViewController.h"
#import "GlobalConfig.h"

@interface SafariExtensionHandler ()
@end

@implementation SafariExtensionHandler {
}
    
- (void)messageReceivedWithName:(NSString *)messageName fromPage:(SFSafariPage *)page userInfo:(NSDictionary *)userInfo {
    GlobalConfig *sharedConfig = [GlobalConfig sharedConfig];
    
    NSMutableArray *params = userInfo[@"params"];
    
    NSLog(@"messageReceived %@ %@", messageName, params);
    
    if ([messageName isEqualToString:@"openTab"]) {
        NSString *ownerTabId = [sharedConfig getNextTabId];
        NSString *url = params[0];
        BOOL active = [params[1] boolValue];
        
        [sharedConfig.openPorts setValue:page forKey:ownerTabId];
        
        [page getContainingTabWithCompletionHandler:^(SFSafariTab * _Nonnull tab) {
            [tab getContainingWindowWithCompletionHandler:^(SFSafariWindow * _Nullable window) {
                [window openTabWithURL:[NSURL URLWithString:url]
                  makeActiveIfPossible:active
                     completionHandler:^(SFSafariTab * _Nullable tab) {
                         NSString *tabId = [sharedConfig getNextTabId];
                         
                         [tab getActivePageWithCompletionHandler:^(SFSafariPage * _Nullable activePage) {
                             [sharedConfig.openPorts setValue:activePage forKey:tabId];
                             [sharedConfig.tabOwnerMap setValue:ownerTabId forKey:tabId];
                             
                             [self sendResponse:page userInfo:userInfo response:@[@(tabId.intValue)]];
                         }];
                     }];
            }];
        }];
    }
    
    NSString *senderTabId = [self getTabId:page];
    
    if(senderTabId) {
        if ([messageName isEqualToString:@"tabUpdated"]) {
            NSString *status = params[0];
            
            [self sendMessageToOwner:@"tabUpdated" from:senderTabId message:@{@"params":@[@(senderTabId.intValue), status]}];
        }
        
        if ([messageName isEqualToString:@"executeScript"]) {
            NSString *tabId = [params[0] stringValue];
            NSString *code = params[1];
            
            SFSafariPage *openPort = [sharedConfig.openPorts valueForKey:tabId];
            
            if(openPort) {
                [self sendMessage:@"executeScript" page:openPort userInfo:nil response:@[code]];
                [self sendResponse:page userInfo:userInfo];
            }else{
                NSLog(@"No tab found: %@", tabId);
            }
        }
        
        if ([messageName isEqualToString:@"getMyTabId"]) {
            [self sendResponse:page userInfo:userInfo response:@[@(senderTabId.intValue)]];
        }
        
        if ([messageName isEqualToString:@"sendToTab"]) {
            NSString *targetTabId = [params[0] stringValue];
            NSDictionary *message = params[1];
            
            SFSafariPage *openPort = [sharedConfig.openPorts valueForKey:targetTabId];
            
            if(openPort) {
                NSLog(@"sendToTab, targetTabId: %@, senderTabId: %@, message: %@", targetTabId, senderTabId, message);
                [self sendMessage:@"message" page:openPort userInfo:nil response:@[message]];
            }else{
                NSLog(@"No target found: %@", targetTabId);
            }
        }
        
        if ([messageName isEqualToString:@"closeCurrentTab"]) {
            if(params && [params count] > 0){
                NSString *tabId = params[0];
                
                if(tabId) {
                    SFSafariPage *openPort = [sharedConfig.openPorts valueForKey:tabId];
                    
                    if(openPort) {
                        [openPort getContainingTabWithCompletionHandler:^(SFSafariTab * _Nonnull tab) {
                            [tab close];
                        }];
                    }
                }
            }else{
                [page getContainingTabWithCompletionHandler:^(SFSafariTab * _Nonnull tab) {
                    [tab close];
                }];
            }
        }
    }else{
        NSLog(@"Not found sender: %@", senderTabId);
    }
}
    
- (NSString *)getTabId:(SFSafariPage*)tab {
    GlobalConfig *sharedConfig = [GlobalConfig sharedConfig];
    NSArray *temp = [sharedConfig.openPorts allKeysForObject:tab];
    NSString *tabId = [temp lastObject];
    
    return tabId;
}
    
- (void)sendResponse:(SFSafariPage *)page userInfo: (NSDictionary *)userInfo {
    [self sendMessage:@"response" page:page userInfo:userInfo response:nil];
}
    
- (void)sendResponse:(SFSafariPage *)page userInfo: (NSDictionary *)userInfo response:(NSArray *)response {
    [self sendMessage:@"response" page:page userInfo:userInfo response:response];
}
    
- (void)sendMessage:(NSString *)type page:(SFSafariPage *)page userInfo: (NSDictionary *)userInfo response: (NSArray *)response {
    NSString *tabId = [self getTabId:page];
    
    if(tabId) {
        NSMutableArray *params = userInfo[@"params"];
        [params insertObject:@(tabId.intValue) atIndex:0];
        [userInfo setValue:params forKey:@"params"];
        
        NSMutableDictionary *responseData = [NSMutableDictionary dictionary];
        
        NSArray *resp;
        
        resp = @[];
        
        if(userInfo) {
            resp = [resp arrayByAddingObjectsFromArray:@[userInfo]];
            responseData[@"params"] = userInfo;
        }
        
        if(response) {
            resp = [resp arrayByAddingObjectsFromArray:response];
            responseData[@"response"] = response;
        }
        
        [page dispatchMessageToScriptWithName:type userInfo:@{@"params": resp}];
    }
}
    
- (void)sendMessageToOwner:(NSString *)type from:(NSString *)senderTabId message:(NSDictionary *)message {
    GlobalConfig *sharedConfig = [GlobalConfig sharedConfig];
    NSString *ownerTabId = sharedConfig.tabOwnerMap[senderTabId];
    
    if(ownerTabId) {
        SFSafariPage *ownerTab = [sharedConfig.openPorts valueForKey:ownerTabId];
        
        if(ownerTab) {
            [ownerTab dispatchMessageToScriptWithName:type userInfo:message];
        }else{
            NSLog(@"sendMessageToOwner, no owner tab: %@", ownerTabId);
        }
    }
}
    
- (void)toolbarItemClickedInWindow:(SFSafariWindow *)window {
    // This method will be called when your toolbar item is clicked.
    NSLog(@"The extension's toolbar item was clicked");
}
    
- (void)validateToolbarItemInWindow:(SFSafariWindow *)window validationHandler:(void (^)(BOOL enabled, NSString *badgeText))validationHandler {
    // This method will be called whenever some state changes in the passed in window. You should use this as a chance to enable or disable your toolbar item and set badge text.
    validationHandler(YES, nil);
}
    
- (SFSafariExtensionViewController *)popoverViewController {
    return [SafariExtensionViewController sharedController];
}
    
@end
