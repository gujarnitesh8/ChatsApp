// =======>>>>>>>> LIBRARIES <<<<<<<<=======

import React from 'react';
import { ScrollView, View, Text, TouchableOpacity, TextInput, } from 'react-native';
import authStyle from './authStyle';
import { Colors, Scale } from '../../CommonConfig';

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
            routes: [{ name: 'Tab' }],
        });
    }
    // =======>>>>>>>> RENDER INITIALIZE <<<<<<<<=======

    render() {
        return (
            <ScrollView contentContainerStyle={authStyle.scrollViewStyle}>
                <View style={authStyle.loginScreeContainer}>
                    <View style={{ height: 250, width: '100%', backgroundColor: Colors.GRAY }}>
                        <View style={{ width: '90%', height: 250, top: 140, alignSelf: 'center', borderRadius: 10, padding: 15, shadowColor: Colors.GRAY, shadowOpacity: 0.4, shadowOffset: { height: 2, width: 0 }, shadowRadius: 8, backgroundColor: Colors.WHITE }}>
                            <Text style={{ color: Colors.MATEBLACK, marginVertical: 4, fontSize: Scale(15), fontWeight: 'bold' }}>Whats App Messanger</Text>
                            <Text style={{ color: Colors.GRAY,  fontSize: Scale(13) }}>Enter your mobile number to Login or Register</Text>
                        </View>
                    </View>
                </View>
            </ScrollView>
        );
    };
}
export default LoginScreen;
