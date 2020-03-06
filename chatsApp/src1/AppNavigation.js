// =======>>>>>>>> LIBRARIES <<<<<<<<=======

import React, { Fragment } from 'react';
import { SafeAreaView, TouchableOpacity, Easing, Image, Button, Animated, StyleSheet, ScrollView, View, Text, StatusBar, } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createDrawerNavigator } from '@react-navigation/drawer';
import HomeScreen from './Screens/HomeModule/HomeScreen';
import LoginScreen from './Screens/AuthModule/LoginScreen';
import { Tabbar } from './TabView';
import StoriesScreen from './Screens/StoriesModule/StoriesScreen';
import PeopleScreen from './Screens/PeopleModule/PeopleScreen';
import { Colors, Scale } from './CommonConfig';
import { SettingDrawer } from './Screens/SettingModule/SettingDrawer';
const Stack = createStackNavigator();
const Tab = createBottomTabNavigator()
const Drawer = createDrawerNavigator();

// =======>>>>>>>> ASSETS <<<<<<<<=======

// =======>>>>>>>> CLASS DECLARATION <<<<<<<<=======

export class App extends React.Component {
  // =======>>>>>>>> STATES DECLARATION <<<<<<<<=======

  // =======>>>>>>>> LIFE CYCLE METHODS <<<<<<<<=======

  componentDidMount() {
    console.disableYellowBox = true
  }
  componentWillUnmount() {
  }

  // =======>>>>>>>> FUNCTIONS DECLARATION <<<<<<<<======= 

  // =======>>>>>>>> RENDER INITIALIZE <<<<<<<<=======

  render() {
    return (
      <NavigationContainer>
        <Drawer.Navigator
          hideStatusBar={false}
          statusBarAnimation={true}
          drawerPosition={'right'}
          drawerType={'slide'}
          overlayColor={1}
          drawerStyle={{
            backgroundColor: Colors.WHITE,
            width: Scale(70)
          }}
          drawerContent={props => <SettingDrawer {...props} />}>
          <Drawer.Screen name="root" component={rootStack} />
          <Drawer.Screen name="Login" component={LoginScreen} />
        </Drawer.Navigator>
      </NavigationContainer>
    );
  };
}
const TabNaviagator = () => {
  return (
    <Tab.Navigator
      initialRouteName="Home"
      backBehavior="initialRoute"
      tabBar={Tabbar}>
      <Tab.Screen name="Home" component={HomeScreen} />
      <Tab.Screen name="Stories" component={StoriesScreen} />
      <Tab.Screen name="People" component={PeopleScreen} />
      <Tab.Screen name="Settings" component={PeopleScreen} />
    </Tab.Navigator >
  )
}
const rootStack = () => {
  return (
    <Stack.Navigator initialRouteName={'Login'}>
      <Stack.Screen name="Login" options={{ headerShown: false }} initialParams={{ username: 'test', password: '1256' }} component={LoginScreen} />
      <Stack.Screen name="Home" component={HomeScreen} />
      <Stack.Screen name="Tab" options={{ headerShown: false }} component={TabNaviagator} />
      <Stack.Screen name="Drawer" component={PeopleScreen} />
    </Stack.Navigator>
  )
}
export default App;
