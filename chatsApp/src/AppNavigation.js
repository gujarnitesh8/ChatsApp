// =======>>>>>>>> LIBRARIES <<<<<<<<=======

import React, { Fragment } from 'react';
import { SafeAreaView, TouchableOpacity, Easing, Image, Button, Animated, StyleSheet, ScrollView, View, Text, StatusBar, } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { createDrawerNavigator } from '@react-navigation/drawer';
import { createMaterialTopTabNavigator } from '@react-navigation/material-top-tabs';

import HomeScreen from './Screens/HomeModule/HomeScreen';
import LoginScreen from './Screens/AuthModule/LoginScreen';
import { Tabbar } from './TabView';
import StoriesScreen from './Screens/StoriesModule/StoriesScreen';
import PeopleScreen from './Screens/PeopleModule/PeopleScreen';
import { Colors, Scale } from './CommonConfig';
import { SettingDrawer } from './Screens/SettingModule/SettingDrawer';
import { ApplicationStyles } from './CommonConfig/ApplicationStyle';
import GroupScreen from './Screens/HomeModule/GroupsScreen';
import ChatScreen from './Screens/HomeModule/ChatScreen';
import CallsScreen from './Screens/HomeModule/CallsScreen';
import ActiveScreen from './Screens/HomeModule/ActiveScreen';

const TopTab = createMaterialTopTabNavigator();
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
    StatusBar.setBarStyle('light-content')
  }
  componentWillUnmount() {
  }

  // =======>>>>>>>> FUNCTIONS DECLARATION <<<<<<<<======= 

  // =======>>>>>>>> RENDER INITIALIZE <<<<<<<<=======

  //--------->>>>> MAIN DRAWER NAVIGATOR <<<<<------------
  render() {
    return (
      <NavigationContainer screenOptions={{ headerShown: false }}>
        <Drawer.Navigator
          hideStatusBar={false}
          statusBarAnimation={true}
          screenOptions={{ headerShown: false }}
          initialRouteName={'root'}
          drawerPosition={'right'}
          drawerType={'slide'}
          overlayColor={1}
          drawerStyle={{
            backgroundColor: Colors.WHITE,
            width: Scale(70)
          }}
          drawerContent={props => <SettingDrawer {...props} />}>
          <Drawer.Screen name="root" component={TabNaviagator} />
          <Drawer.Screen name="Login" component={LoginScreen} />
        </Drawer.Navigator>
      </NavigationContainer>
    );
  };
}

//--------->>>>> MAIN DRAWER NAVIGATOR <<<<<------------
function TabNaviagator({ navigation, route }) {
  return (
    <Tab.Navigator
      initialRouteName="Home"
      backBehavior="initialRoute"
      tabBar={Tabbar}
    >
      <Tab.Screen name="Home" component={HomeStack} />
      <Tab.Screen name="Stories" component={StoriesStack} />
      <Tab.Screen name="Peoples" component={PeopleStack} />
      <Tab.Screen name="Settings" component={SettingStack} />
    </Tab.Navigator >
  )
}
function topTabView() {
  return (
    <TopTab.Navigator
      tabBarOptions={{
        labelStyle: { fontSize: 14, fontWeight: 'bold' },
        indicatorStyle: { backgroundColor: Colors.WHITE },
        activeTintColor: Colors.WHITE,
        style: { backgroundColor: Colors.APPCOLOR },
      }}
    >
      <TopTab.Screen name="Chats" component={ChatScreen} />
      <TopTab.Screen name="Groups" component={GroupScreen} />
      <TopTab.Screen name="Calls" component={CallsScreen} />
      <TopTab.Screen name="Active" component={ActiveScreen} />
    </TopTab.Navigator>
  )
}
//--------->>>>> HOME TAB STACK <<<<<------------
function HomeStack() {
  return (
    <Stack.Navigator>
      <Stack.Screen name="Home" options={{
        title: 'Home',
        headerLeft: () => <View />,
        headerRight: () => <View />,
        headerStyle: ApplicationStyles.headerStyle,
        headerTitleStyle: ApplicationStyles.headerTitleStyle,
      }} component={topTabView} />
    </Stack.Navigator>
  );
}

//--------->>>>> STORIES TAB STACK <<<<<------------
function StoriesStack() {
  return (
    <Stack.Navigator>
      <Stack.Screen name="Stories" component={StoriesScreen} />
    </Stack.Navigator>
  );
}

//--------->>>>> PEOPLES TAB STACK <<<<<------------
function PeopleStack() {
  return (
    <Stack.Navigator>
      <Stack.Screen name="People" component={PeopleScreen} />
    </Stack.Navigator>
  );
}

//--------->>>>> SETTINGS TAB STACK <<<<<------------
function SettingStack() {
  return (
    <Stack.Navigator>
      <Stack.Screen name="Settings" component={SettingDrawer} />
    </Stack.Navigator>
  );
}
export default App;
