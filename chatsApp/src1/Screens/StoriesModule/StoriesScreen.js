// =======>>>>>>>> LIBRARIES <<<<<<<<=======

import React, { Fragment } from 'react';
import { SafeAreaView, StyleSheet, ScrollView, View, Text, StatusBar, TouchableOpacity, Image, FlatList, } from 'react-native';
import { connect } from 'react-redux';

// =======>>>>>>>> ASSETS <<<<<<<<=======
import storiesStyle from './storiesStyle';
import { Colors, Scale, ImagesPath, LoadWheel, ShineLoader } from '../../CommonConfig';
import Card from './Components/Card';
import { getAlbumListRequest } from '../../Redux/Actions'


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
        this.callGetAlbum()
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
            headerLeft: () => <TouchableOpacity><Image source={ImagesPath.MenuIcon} style={storiesStyle.menuIconStyle} /></TouchableOpacity>,
            headerStyle: {
                backgroundColor: Colors.ORANGE
            },
            headerTitleStyle: {
                color: Colors.WHITE,
                fontSize: Scale(18)
            }
        })
    }
    callGetAlbum() {
        this.setState({ isLoading_getAlbum: true })
        this.props.getAlbumListRequest()
    }

    // =======>>>>>>>> RENDER INITIALIZE <<<<<<<<=======
    rendeItem({ item, index }) {
        return (
            <Card>
                <View style={storiesStyle.cardInnerContainer}>
                    <View style={storiesStyle.cardHeaderStyle}>
                        <Text style={storiesStyle.cardHeaderTextStyle}>{item.title}</Text>
                    </View>
                    {/* <Image source={{ uri: item.url }} style={{ width: '100%', marginVertical: 15, height: 50 }} /> */}
                    <Text style={storiesStyle.cardDescriptionTextStyle}>
                        {`Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s`}
                    </Text>
                </View>
            </Card>
        )
    }
    render() {
        return (
            <ScrollView showsVerticalScrollIndicator={false} style={storiesStyle.homeScreeContainer}>
                {/* <FlatList
                    data={this.state.cardList}
                    extraData={this.state}
                    scrollEnabled={false}
                    renderItem={this.rendeItem.bind(this)}
                /> */}
                {/* <ShineLoader visible={this.state.isLoading_getAlbum} /> */}
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