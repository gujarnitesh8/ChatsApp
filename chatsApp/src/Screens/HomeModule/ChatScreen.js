// =======>>>>>>>> LIBRARIES <<<<<<<<=======

import React, { Fragment } from 'react';
import { SafeAreaView, StyleSheet, ScrollView, View, Text, StatusBar, TouchableOpacity, Image, FlatList, ImageBackground, } from 'react-native';
import { connect } from 'react-redux';
import Ripple from 'react-native-material-ripple';

// =======>>>>>>>> ASSETS <<<<<<<<=======
import homeStyle from './homeStyle';
import { Colors, Scale, ImagesPath, LoadWheel, ShineLoader, ApplicationStyles } from '../../CommonConfig';
import Card from './Components/Card';
import { getAlbumListRequest } from '../../Redux/Actions'

// =======>>>>>>>> CLASS DECLARATION <<<<<<<<=======

export class ChatScreen extends React.Component {
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
                cardList: [
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSO1Awoj9EJMFEIRo0EAs6GnR4Xsulbgefvh6XFVckdPA43yarwUw&s', name: 'Elina Fuinit', time: '02:45 AM', messageStatus: 2, lastMessage: "This is test message", unreadCount: 1 },
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSH5VG9m1ylrb5mUhjEn1O8ct1ZLRou_HqGSiFK0V3wATXEHbF0Uw&s', name: 'Ramnath Kovind', time: '04:05 AM', messageStatus: 1, lastMessage: "Hi there! I see you are coming from the U.S. We do ship there, is there anything I can help with?", unreadCount: 0 },
                    { image: 'https://images.unsplash.com/photo-1529665253569-6d01c0eaf7b6?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=1080&fit=max&ixid=eyJhcHBfaWQiOjEyMDd9', name: 'Jonh Pathan', time: '04:40 AM', messageStatus: 0, lastMessage: "there anything I can help with?", unreadCount: 0 },
                    { image: 'https://media.gettyimages.com/photos/beautiful-woman-posing-against-dark-background-picture-id638756792?s=612x612', name: 'Sima John', time: '04:58 AM', messageStatus: 2, lastMessage: "How are you?", unreadCount: 6 },
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRCqRVqhG3VDySOb8_M_iVJQDK4D_PFItdbjBULn-4ZAZrtIQCy&s', name: 'Donald Doc', time: '04:59 AM', messageStatus: 2, lastMessage: "hi, there", unreadCount: 0 },
                    { image: 'https://cdn7.dissolve.com/p/D2012_324_035/D2012_324_035_1200.jpg', name: 'Ronald Portar', time: '05:09 AM', messageStatus: 1, lastMessage: "I'm happy to help you..", unreadCount: 2 },
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSO1Awoj9EJMFEIRo0EAs6GnR4Xsulbgefvh6XFVckdPA43yarwUw&s', name: 'Elina Fuinit', time: '02:45 AM', messageStatus: 0, lastMessage: "This is test message", unreadCount: 0 },
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSH5VG9m1ylrb5mUhjEn1O8ct1ZLRou_HqGSiFK0V3wATXEHbF0Uw&s', name: 'Ramnath Kovind', time: '04:05 AM', messageStatus: 1, lastMessage: "Hi there! I see you are coming from the U.S. We do ship there, is there anything I can help with?", unreadCount: 0 },
                    { image: 'https://images.unsplash.com/photo-1529665253569-6d01c0eaf7b6?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=1080&fit=max&ixid=eyJhcHBfaWQiOjEyMDd9', name: 'Jonh Pathan', time: '04:40 AM', messageStatus: 0, lastMessage: "there anything I can help with?", unreadCount: 0 },
                    { image: 'https://media.gettyimages.com/photos/beautiful-woman-posing-against-dark-background-picture-id638756792?s=612x612', name: 'Sima John', time: '04:58 AM', messageStatus: 0, lastMessage: "How are you?", unreadCount: 0 },
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRCqRVqhG3VDySOb8_M_iVJQDK4D_PFItdbjBULn-4ZAZrtIQCy&s', name: 'Donald Doc', time: '04:59 AM', messageStatus: 0, lastMessage: "hi, there", unreadCount: 0 },
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSO1Awoj9EJMFEIRo0EAs6GnR4Xsulbgefvh6XFVckdPA43yarwUw&s', name: 'Elina Fuinit', time: '02:45 AM', messageStatus: 2, lastMessage: "This is test message", unreadCount: 1 },
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSH5VG9m1ylrb5mUhjEn1O8ct1ZLRou_HqGSiFK0V3wATXEHbF0Uw&s', name: 'Ramnath Kovind', time: '04:05 AM', messageStatus: 1, lastMessage: "Hi there! I see you are coming from the U.S. We do ship there, is there anything I can help with?", unreadCount: 0 },
                    { image: 'https://images.unsplash.com/photo-1529665253569-6d01c0eaf7b6?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=1080&fit=max&ixid=eyJhcHBfaWQiOjEyMDd9', name: 'Jonh Pathan', time: '04:40 AM', messageStatus: 0, lastMessage: "there anything I can help with?", unreadCount: 0 },
                    { image: 'https://media.gettyimages.com/photos/beautiful-woman-posing-against-dark-background-picture-id638756792?s=612x612', name: 'Sima John', time: '04:58 AM', messageStatus: 2, lastMessage: "How are you?", unreadCount: 6 },
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRCqRVqhG3VDySOb8_M_iVJQDK4D_PFItdbjBULn-4ZAZrtIQCy&s', name: 'Donald Doc', time: '04:59 AM', messageStatus: 2, lastMessage: "hi, there", unreadCount: 0 },
                    { image: 'https://cdn7.dissolve.com/p/D2012_324_035/D2012_324_035_1200.jpg', name: 'Ronald Portar', time: '05:09 AM', messageStatus: 1, lastMessage: "I'm happy to help you..", unreadCount: 2 },
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSO1Awoj9EJMFEIRo0EAs6GnR4Xsulbgefvh6XFVckdPA43yarwUw&s', name: 'Elina Fuinit', time: '02:45 AM', messageStatus: 0, lastMessage: "This is test message", unreadCount: 0 },
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSH5VG9m1ylrb5mUhjEn1O8ct1ZLRou_HqGSiFK0V3wATXEHbF0Uw&s', name: 'Ramnath Kovind', time: '04:05 AM', messageStatus: 1, lastMessage: "Hi there! I see you are coming from the U.S. We do ship there, is there anything I can help with?", unreadCount: 0 },
                    { image: 'https://images.unsplash.com/photo-1529665253569-6d01c0eaf7b6?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=1080&fit=max&ixid=eyJhcHBfaWQiOjEyMDd9', name: 'Jonh Pathan', time: '04:40 AM', messageStatus: 0, lastMessage: "there anything I can help with?", unreadCount: 0 },
                    { image: 'https://media.gettyimages.com/photos/beautiful-woman-posing-against-dark-background-picture-id638756792?s=612x612', name: 'Sima John', time: '04:58 AM', messageStatus: 0, lastMessage: "How are you?", unreadCount: 0 },
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRCqRVqhG3VDySOb8_M_iVJQDK4D_PFItdbjBULn-4ZAZrtIQCy&s', name: 'Donald Doc', time: '04:59 AM', messageStatus: 0, lastMessage: "hi, there", unreadCount: 0 },
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSO1Awoj9EJMFEIRo0EAs6GnR4Xsulbgefvh6XFVckdPA43yarwUw&s', name: 'Elina Fuinit', time: '02:45 AM', messageStatus: 2, lastMessage: "This is test message", unreadCount: 1 },
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSH5VG9m1ylrb5mUhjEn1O8ct1ZLRou_HqGSiFK0V3wATXEHbF0Uw&s', name: 'Ramnath Kovind', time: '04:05 AM', messageStatus: 1, lastMessage: "Hi there! I see you are coming from the U.S. We do ship there, is there anything I can help with?", unreadCount: 0 },
                    { image: 'https://images.unsplash.com/photo-1529665253569-6d01c0eaf7b6?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=1080&fit=max&ixid=eyJhcHBfaWQiOjEyMDd9', name: 'Jonh Pathan', time: '04:40 AM', messageStatus: 0, lastMessage: "there anything I can help with?", unreadCount: 0 },
                    { image: 'https://media.gettyimages.com/photos/beautiful-woman-posing-against-dark-background-picture-id638756792?s=612x612', name: 'Sima John', time: '04:58 AM', messageStatus: 2, lastMessage: "How are you?", unreadCount: 6 },
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRCqRVqhG3VDySOb8_M_iVJQDK4D_PFItdbjBULn-4ZAZrtIQCy&s', name: 'Donald Doc', time: '04:59 AM', messageStatus: 2, lastMessage: "hi, there", unreadCount: 0 },
                    { image: 'https://cdn7.dissolve.com/p/D2012_324_035/D2012_324_035_1200.jpg', name: 'Ronald Portar', time: '05:09 AM', messageStatus: 1, lastMessage: "I'm happy to help you..", unreadCount: 2 },
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSO1Awoj9EJMFEIRo0EAs6GnR4Xsulbgefvh6XFVckdPA43yarwUw&s', name: 'Elina Fuinit', time: '02:45 AM', messageStatus: 0, lastMessage: "This is test message", unreadCount: 0 },
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSH5VG9m1ylrb5mUhjEn1O8ct1ZLRou_HqGSiFK0V3wATXEHbF0Uw&s', name: 'Ramnath Kovind', time: '04:05 AM', messageStatus: 1, lastMessage: "Hi there! I see you are coming from the U.S. We do ship there, is there anything I can help with?", unreadCount: 0 },
                    { image: 'https://images.unsplash.com/photo-1529665253569-6d01c0eaf7b6?ixlib=rb-1.2.1&q=80&fm=jpg&crop=entropy&cs=tinysrgb&w=1080&fit=max&ixid=eyJhcHBfaWQiOjEyMDd9', name: 'Jonh Pathan', time: '04:40 AM', messageStatus: 0, lastMessage: "there anything I can help with?", unreadCount: 0 },
                    { image: 'https://media.gettyimages.com/photos/beautiful-woman-posing-against-dark-background-picture-id638756792?s=612x612', name: 'Sima John', time: '04:58 AM', messageStatus: 0, lastMessage: "How are you?", unreadCount: 0 },
                    { image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRCqRVqhG3VDySOb8_M_iVJQDK4D_PFItdbjBULn-4ZAZrtIQCy&s', name: 'Donald Doc', time: '04:59 AM', messageStatus: 0, lastMessage: "hi, there", unreadCount: 0 },
                    { image: 'https://cdn7.dissolve.com/p/D2012_324_035/D2012_324_035_1200.jpg', name: 'Ronald Portar', time: '05:09 AM', messageStatus: 1, lastMessage: "I'm happy to help you..", unreadCount: 0 },
                ]
            })
        }, 3000);
        this.setHeader()
        this.callGetAlbum()
    }
    componentDidUpdate(prevProps) {
        // if (this.state.isLoading_getAlbum && (this.props.Home.albumList != prevProps.Home.albumList)) {
        //     //here we have to check for API success and failure codes if any
        //     this.setState({ cardList: this.props.Home.albumList, isLoading_getAlbum: false })
        // }
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
        this.props.getAlbumListRequest && this.props.getAlbumListRequest()
    }

    // =======>>>>>>>> RENDER INITIALIZE <<<<<<<<=======
    renderItem = ({ item, index }) => {
        return (
            <Card>
                <Ripple rippleDuration={500} rippleColor={Colors.APPCOLOR}>
                    <View style={homeStyle.cardInnerContainer}>
                        <View style={{ flex: 0.2, justifyContent: 'center' }}>
                            <Image style={{ height: 60, width: 60, borderRadius: 35 }} source={{ uri: item.image }} />
                        </View>
                        <View style={{ flex: 0.8, height: '100%', justifyContent: 'space-evenly' }}>
                            <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                                <Text style={homeStyle.cardHeaderTextStyle}>{item.name}</Text>
                                <Text style={homeStyle.cardTimeTextStyle} numberOfLines={1}>{item.time}</Text>
                            </View>
                            <View style={{ flexDirection: 'row', justifyContent: 'space-between' }}>
                                <Text style={homeStyle.cardDescriptionTextStyle} numberOfLines={1}>
                                    {item.lastMessage}
                                </Text>
                                {item.unreadCount > 0 ?
                                    <View style={{ backgroundColor: Colors.APPCOLOR, borderRadius: 15, justifyContent: 'center', alignItems: 'center', width: 22, height: 22 }}>
                                        <Text style={{ color: Colors.WHITE, fontSize: 12 }}>{item.unreadCount}</Text>
                                    </View>
                                    :
                                    <Image source={item.messageStatus == 0 ? ImagesPath.RightCheckIcon : ImagesPath.DoubleRightCheckIcon} resizeMode={'contain'} style={{ height: item.messageStatus == 0 ? 15 : 20, width: item.messageStatus == 0 ? 15 : 20, alignSelf: 'flex-end', tintColor: item.messageStatus == 0 ? Colors.GRAY : item.messageStatus == 1 ? Colors.GRAY : Colors.APPCOLOR }} />
                                }
                            </View>
                        </View>
                    </View>
                </Ripple>
            </Card >
        )
    }
    renderStoriesItem = ({ item, index }) => {
        return (
            <View style={{ height: 150, width: 70, marginHorizontal: 5 }}>
                <ImageBackground source={{ uri: item.image }} imageStyle={{ borderRadius: 5 }} style={{ height: 100, width: 70 }}>
                    {/* <Image source={{}}/> */}
                </ImageBackground>
            </View>
        )
    }
    render() {
        return (
            <View style={homeStyle.homeScreenViewContainer}>
                <ScrollView showsVerticalScrollIndicator={false} style={homeStyle.homeScreenContainer}>
                    <FlatList
                        data={this.state.cardList}
                        showsHorizontalScrollIndicator={false}
                        extraData={this.state}
                        horizontal={true}
                        style={{ flex: 1 }}
                        renderItem={this.renderStoriesItem.bind(this)}
                    />
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
export default connect(mapStateToProps, { getAlbumListRequest })(ChatScreen);