import React from 'react';
import { StyleSheet } from 'react-native'
import { Scale, Colors } from '../../CommonConfig';
import { screenHeight } from '../../CommonConfig/HelperFunctions/functions';

const homeStyle = StyleSheet.create({
    homeScreenViewContainer: {
        flex: 1,
    },
    homeScreenContainer: {
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
        // borderLeftColor: Colors.APPCOLOR,
        // borderLeftWidth: Scale(4),
        marginVertical: Scale(5),
        alignSelf: 'center',
        borderRadius: Scale(5),
        shadowOffset: { height: Scale(5), width: Scale(0) },
        shadowOpacity: 0.1,
        shadowColor: Colors.GRAY,
        backgroundColor: Colors.WHITE
    },
    cardInnerContainer: {
        padding: Scale(10),
        flexDirection: 'row',
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
    cardTimeTextStyle: {
        fontSize: Scale(12),
        color: Colors.GRAY
    },
    cardDescriptionTextStyle: {
        fontSize: Scale(14),
        flex: 0.95,
        color: Colors.GRAY
    },
    listEmptyTextStyle: {
        color: Colors.GRAY
    },
    listEmptyContainer: {
        width: '100%',
        flex: 1,
        height: screenHeight - Scale(200),
        justifyContent: 'center',
        alignItems: 'center'
    }
})
export default homeStyle;