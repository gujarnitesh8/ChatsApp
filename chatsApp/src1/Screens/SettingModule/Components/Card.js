// =======>>>>>>>> LIBRARIES <<<<<<<<=======
import React, { Fragment } from 'react';
import { View } from 'react-native';
// =======>>>>>>>> ASSETS <<<<<<<<=======
import settingStyle from "../settingStyle"

const Card = (props) => {
    return (
        <View style={settingStyle.cardContainer}>
            {props.children}
        </View>
    )
}
export default Card