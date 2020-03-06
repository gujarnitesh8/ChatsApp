// =======>>>>>>>> LIBRARIES <<<<<<<<=======

import React, { Fragment } from 'react';
import { SafeAreaView, StyleSheet, ScrollView, View, Text, StatusBar, TouchableOpacity, Image, FlatList, } from 'react-native';
import { connect } from 'react-redux';

// =======>>>>>>>> ASSETS <<<<<<<<=======
import peopleStyle from './peopleStyle';
import { Colors, Scale, ImagesPath, LoadWheel, ShineLoader } from '../../CommonConfig';
import Card from './Components/Card';
import { getAlbumListRequest } from '../../Redux/Actions'
import { ApplicationStyles } from '../../CommonConfig/ApplicationStyle';


// =======>>>>>>>> CLASS DECLARATION <<<<<<<<=======

export class HomeScreen extends React.Component {
    // =======>>>>>>>> STATES DECLARATION <<<<<<<<=======
    state = {
        cardList: [],
        isLoading_getAlbum: false
    }
    // =======>>>>>>>> LIFE CYCLE METHODS <<<<<<<<=======

    componentDidMount() {
        console.disableYellowBox = true //warning disable line
        this.setHeader()

    }
    componentDidUpdate(prevProps) {

    }

    // =======>>>>>>>> FUNCTIONS DECLARATION <<<<<<<<=======
    setHeader() {
        this.props.navigation.setOptions({
            headerTitle: 'People',
            // headerLeft: () => <TouchableOpacity><Image source={ImagesPath.MenuIcon} style={peopleStyle.menuIconStyle} /></TouchableOpacity>,
            headerStyle: ApplicationStyles.headerStyle,
            headerTitleStyle: ApplicationStyles.headerTitleStyle
        })
    }
    // =======>>>>>>>> RENDER INITIALIZE <<<<<<<<=======

    render() {
        return (
            <ScrollView showsVerticalScrollIndicator={false} style={peopleStyle.homeScreeContainer}>

            </ScrollView>
        );
    };
}

// =======>>>>>>>> PROPS CONNECTION <<<<<<<<=======
const mapStateToProps = (res) => {
    return {
        Auth: res.Auth,
        Home: res.Home,
        isLoading: res.Common.isLoading ? res.Common.isLoading : false
    };
}

// =======>>>>>>>> REDUX CONNECTION <<<<<<<<=======
export default connect(mapStateToProps, { getAlbumListRequest })(HomeScreen);