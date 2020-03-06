import React from 'react';
import { StyleSheet } from 'react-native'
import { Colors, Scale } from '../../CommonConfig';

const authStyle = StyleSheet.create({
    scrollViewStyle: {
        flex: 1,
    },
    loginScreeContainer: {
        flex: 1,
        backgroundColor: Colors.MATEBLACK,
        justifyContent: 'center',
        alignItems: 'center'
    },
    loginHeaderTextContainer: {
        height: Scale(55)
    },
    loginBoxHeaderTextStyle: {
        fontWeight: '600',
        fontSize: Scale(22),
        color: Colors.APPCOLOR
    },
    loginBoxCard: {
        width: '85%',
        borderRadius: Scale(5),
        justifyContent: 'center',
        paddingVertical: Scale(15),
        alignItems: 'center',
        backgroundColor: Colors.WHITE
    },
    textInputBoxStyle: {
        height: Scale(60),
        width: '100%',
        paddingHorizontal: Scale(15),
        justifyContent: 'center'
    },
    textInputStyle: {
        borderBottomColor: Colors.APPCOLOR,
        height: Scale(40),
        borderBottomWidth: 1,
    },
    labelTextStyle: {
        fontSize: Scale(10),
        color: Colors.APPCOLOR
    },
    loginButtonContainer: {
        height: Scale(40),
        width: '85%',
        marginTop: Scale(15),
        borderRadius: Scale(5),
        backgroundColor: Colors.APPCOLOR,
        justifyContent: 'center',
        alignItems: 'center'
    },
    signInButtonTextStyle: {
        color: Colors.WHITE,
        fontWeight: '600',
        fontSize: Scale(15)
    }
})
export default authStyle;