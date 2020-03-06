// =======>>>>>>>> LIBRARIES <<<<<<<<=======

import React, { Fragment } from 'react';
import { SafeAreaView, StyleSheet, ScrollView, View, Text, StatusBar, TouchableOpacity, Image, FlatList, } from 'react-native';
import { connect } from 'react-redux';
import Ripple from 'react-native-material-ripple';

// =======>>>>>>>> ASSETS <<<<<<<<=======
import homeStyle from './homeStyle';
import { Colors, Scale, ImagesPath, LoadWheel, ShineLoader, ApplicationStyles } from '../../CommonConfig';
import Card from './Components/Card';
import { getAlbumListRequest } from '../../Redux/Actions'


// =======>>>>>>>> CLASS DECLARATION <<<<<<<<=======

export class CallsScreen extends React.Component {
    // =======>>>>>>>> STATES DECLARATION <<<<<<<<=======
    state = {
        cardList: [],
        isLoading_getAlbum: true
    }
    // =======>>>>>>>> LIFE CYCLE METHODS <<<<<<<<=======

    componentDidMount() {
        console.disableYellowBox = true //warning disable line
        setTimeout(() => {
            this.setState({
                isLoading_getAlbum: false,
                cardList: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20]
            })
        }, 3000);
        this.setHeader()
        // this.callGetAlbum()
    }
    componentDidUpdate(prevProps) {
        if (this.state.isLoading_getAlbum && (this.props.Home.albumList != prevProps.Home.albumList)) {
            //here we have to check for API success and failure codes if any
            this.setState({ cardList: this.props.Home.albumList, isLoading_getAlbum: false })
        }
    }

    // =======>>>>>>>> FUNCTIONS DECLARATION <<<<<<<<=======
    setHeader() {
        this.props.navigation.setOptions({
            headerTitle: 'Home',
            headerLeft: () => <TouchableOpacity><Image source={ImagesPath.PlusIcon} style={homeStyle.menuIconStyle} /></TouchableOpacity>,
            headerStyle: ApplicationStyles.headerStyle,
            headerTitleStyle: ApplicationStyles.headerTitleStyle
        })
    }
    callGetAlbum() {
        this.setState({ isLoading_getAlbum: true })
        this.props.getAlbumListRequest()
    }

    // =======>>>>>>>> RENDER INITIALIZE <<<<<<<<=======
    renderItem = () => {
        return (
            <Card>
                <Ripple rippleDuration={500} rippleColor={Colors.APPCOLOR}>
                    <View style={homeStyle.cardInnerContainer}>
                        <Text style={homeStyle.cardDescriptionTextStyle}>
                            {`Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s`}
                        </Text>
                    </View>
                </Ripple>
            </Card>
        )
    }

    render() {
        return (
            <View style={homeStyle.homeScreenViewContainer}>
                <ScrollView showsVerticalScrollIndicator={false} style={homeStyle.homeScreenContainer}>
                    <FlatList
                        data={this.state.cardList}
                        extraData={this.state}
                        style={{ flex: 1 }}
                        renderItem={this.renderItem.bind(this)}
                        ListEmptyComponent={() => {
                            return this.state.isLoading_getAlbum ? <ShineLoader visible={this.state.isLoading_getAlbum} /> : <View style={homeStyle.listEmptyContainer}><Text style={homeStyle.listEmptyTextStyle}>opps, No records found</Text></View>
                        }}
                    />
                </ScrollView>
            </View>
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
export default connect(mapStateToProps, { getAlbumListRequest })(CallsScreen);