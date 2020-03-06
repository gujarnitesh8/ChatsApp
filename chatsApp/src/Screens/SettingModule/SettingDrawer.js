import * as React from 'react';
import { View, Text, TouchableOpacity, Image } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import {
    createDrawerNavigator,
    DrawerContentScrollView,
    DrawerItemList,
    DrawerItem,
} from '@react-navigation/drawer';
import Animated from 'react-native-reanimated';
import { ImagesPath, Colors, Scale } from '../../CommonConfig';
import { screenHeight } from '../../CommonConfig/HelperFunctions/functions';
import { CommonActions } from '@react-navigation/native';

export function SettingDrawer({ progress, ...rest }) {
    const translateX = Animated.interpolate(progress, {
        inputRange: [0, 1],
        outputRange: [-100, 0],
    });

    return (
        <DrawerContentScrollView {...rest}>
            <Animated.View style={{ transform: [{ translateX }], height: screenHeight - Scale(80), alignItems: 'center' }}>
                {/* <DrawerItemList {...rest} /> */}
                {/* <DrawerItem label="Help" onPress={() => alert('Link to help')} /> */}
                <TouchableOpacity onPress={() => rest.navigation.navigate('Home')} style={{ width: Scale(45), backgroundColor: Colors.TEAL, marginVertical: Scale(15), height: Scale(45), justifyContent: 'center', alignItems: 'center' }}>
                    <Image source={ImagesPath.PeopleActiveIcon} style={{ height: Scale(25), tintColor: Colors.WHITE, width: Scale(25) }} />
                </TouchableOpacity>

                <TouchableOpacity style={{ width: Scale(45), backgroundColor: Colors.OLIVE, marginVertical: Scale(15), height: Scale(45), justifyContent: 'center', alignItems: 'center' }}>
                    <Image source={ImagesPath.KeyIcon} style={{ height: Scale(25), tintColor: Colors.WHITE, width: Scale(25) }} />
                </TouchableOpacity>

                <TouchableOpacity style={{ width: Scale(45), backgroundColor: Colors.MAROON, marginVertical: Scale(15), height: Scale(45), justifyContent: 'center', alignItems: 'center' }}>
                    <Image source={ImagesPath.LockIcon} style={{ height: Scale(25), tintColor: Colors.WHITE, width: Scale(25) }} />
                </TouchableOpacity>

                <TouchableOpacity style={{ width: Scale(45), backgroundColor: Colors.NAVY, marginVertical: Scale(15), height: Scale(45), justifyContent: 'center', alignItems: 'center' }}>
                    <Image source={ImagesPath.NotificationIcon} style={{ height: Scale(25), tintColor: Colors.WHITE, width: Scale(25) }} />
                </TouchableOpacity>

                <TouchableOpacity style={{ width: Scale(45), backgroundColor: Colors.PURPLE, marginVertical: Scale(15), height: Scale(45), justifyContent: 'center', alignItems: 'center' }}>
                    <Image source={ImagesPath.SettingsActiveIcon} style={{ height: Scale(25), tintColor: Colors.WHITE, width: Scale(25) }} />
                </TouchableOpacity>

                <TouchableOpacity onPress={() => {
                    //here will dispatch action to navigate user back on login screen.
                    rest.navigation.dispatch(
                        CommonActions.reset({
                            index: 1,
                            routes: [
                                { name: 'Login' }
                            ],
                        })
                    );
                }} style={{ width: Scale(45), position: 'absolute', bottom: Scale(5), backgroundColor: Colors.MATEBLACK, marginVertical: Scale(15), height: Scale(45), justifyContent: 'center', alignItems: 'center' }}>
                    <Image source={ImagesPath.LogOutIcon} style={{ height: Scale(25), tintColor: Colors.WHITE, width: Scale(25) }} />
                </TouchableOpacity>

            </Animated.View>
        </DrawerContentScrollView>
    );
}
