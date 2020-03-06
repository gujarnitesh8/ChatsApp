import React from 'react';
import { StyleSheet } from 'react-native'
import { Scale, Colors } from '../../CommonConfig';

const settingStyle = StyleSheet.create({
    homeScreeContainer: {
        flex: 1,
        paddingVertical: Scale(10)
    },
    menuIconStyle: {
        height: Scale(22),
        width: Scale(22),
        marginLeft: Scale(15),
        tintColor: Colors.WHITE,
    },
    cardContainer: {
        elevation: 3,
        width: '95%',
        borderLeftColor: Colors.ORANGE,
        borderLeftWidth: Scale(4),
        marginVertical: Scale(10),
        alignSelf: 'center',
        borderRadius: Scale(5),
        shadowOffset: { height: Scale(5), width: Scale(0) },
        shadowOpacity: 0.1,
        shadowColor: Colors.GRAY,
        backgroundColor: Colors.WHITE
    },
    cardInnerContainer: {
        padding: Scale(15),
        flex: 1
    },
    cardHeaderStyle: {
        flex: 0.5,
        justifyContent: 'center',
    },
    cardHeaderTextStyle: {
        color: Colors.MATEBLACK,
        fontSize: Scale(17),
        fontWeight: '600'
    },
    cardDescriptionTextStyle: {
        fontSize: Scale(12),
        marginTop: Scale(5),
        color: Colors.GRAY
    }
})
export default settingStyle;