// =======>>>>>>>> LIBRARIES <<<<<<<<=======

import React from 'react';
import { ScrollView, View, Text, TouchableOpacity, TextInput, StatusBar, } from 'react-native';
import authStyle from './authStyle';
import { Colors } from '../../CommonConfig';

// =======>>>>>>>> ASSETS <<<<<<<<=======


// =======>>>>>>>> CLASS DECLARATION <<<<<<<<=======

export class LoginScreen extends React.Component {

    // =======>>>>>>>> STATES DECLARATION <<<<<<<<=======

    // =======>>>>>>>> LIFE CYCLE METHODS <<<<<<<<=======

    componentDidMount() {

    }
    componentDidUpdate(prevProps) {

    }
    componentWillUnmount() {

    }

    // =======>>>>>>>> FUNCTIONS DECLARATION <<<<<<<<=======
    onSignInPress() {
        this.props.navigation.reset({
            index: 0,
            routes: [{ name: 'root' }],
        });
    }
    // =======>>>>>>>> RENDER INITIALIZE <<<<<<<<=======

    render() {
        return (
            <ScrollView contentContainerStyle={authStyle.scrollViewStyle}>
                <StatusBar barStyle={'light-content'} backgroundColor={Colors.APPCOLOR} />
                <View style={authStyle.loginScreeContainer}>
                    <View style={authStyle.loginHeaderTextContainer}>
                        <Text style={authStyle.loginBoxHeaderTextStyle}>Basic Folder Structure</Text>
                    </View>
                    <View style={authStyle.loginBoxCard}>
                        <View style={authStyle.textInputBoxStyle}>
                            <Text style={authStyle.labelTextStyle}>EMAIL</Text>
                            <TextInput placeholder={'john@gmail.com'} style={authStyle.textInputStyle} />
                        </View>
                        <View style={authStyle.textInputBoxStyle}>
                            <Text style={authStyle.labelTextStyle}>PASSWORD</Text>
                            <TextInput placeholder={'*******'} secureTextEntry={true} style={authStyle.textInputStyle} />
                        </View>
                    </View>
                    <TouchableOpacity onPress={this.onSignInPress.bind(this)} style={authStyle.loginButtonContainer}>
                        <Text style={authStyle.signInButtonTextStyle}>SIGN IN</Text>
                    </TouchableOpacity>
                </View>
            </ScrollView>
        );
    };
}
export default LoginScreen;
