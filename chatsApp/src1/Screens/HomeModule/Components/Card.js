// =======>>>>>>>> LIBRARIES <<<<<<<<=======
import React, { Fragment } from 'react';
import { View } from 'react-native';
// =======>>>>>>>> ASSETS <<<<<<<<=======
import homeStyle from "../homeStyle"

const Card = (props) => {
    return (
        <View style={homeStyle.cardContainer}>
            {props.children}
        </View>
    )
}
export default Card