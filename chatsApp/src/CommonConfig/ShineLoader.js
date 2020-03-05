import React, { Component } from 'react'
import { View, Modal } from 'react-native';
import {
    Placeholder,
    PlaceholderMedia,
    PlaceholderLine,
    Shine,
    Fade
} from "rn-placeholder";
export const ShineLoader = ({ visible }) => {
    if (visible) {

        return (
            <View style={{ backgroundColor: 'white' }}>
                <View visible={visible} animated={true} style={{ padding: 10 }}>
                    <Placeholder Animation={Shine} Left={PlaceholderMedia}>
                        <PlaceholderLine width={80} />
                        <PlaceholderLine />
                        <PlaceholderLine width={30} />
                    </Placeholder>
                </View>

                <View visible={visible} animated={true} style={{ padding: 10 }}>
                    <Placeholder Animation={Shine} Left={PlaceholderMedia}>
                        <PlaceholderLine width={80} />
                        <PlaceholderLine />
                        <PlaceholderLine width={30} />
                    </Placeholder>
                </View>

                <View visible={visible} animated={true} style={{ padding: 10 }}>
                    <Placeholder Animation={Shine} Left={PlaceholderMedia}>
                        <PlaceholderLine width={80} />
                        <PlaceholderLine />
                        <PlaceholderLine width={30} />
                    </Placeholder>
                </View>

                <View visible={visible} animated={true} style={{ padding: 10 }}>
                    <Placeholder Animation={Shine} Left={PlaceholderMedia}>
                        <PlaceholderLine width={80} />
                        <PlaceholderLine />
                        <PlaceholderLine width={30} />
                    </Placeholder>
                </View>

                <View visible={visible} animated={true} style={{ padding: 10 }}>
                    <Placeholder Animation={Shine} Left={PlaceholderMedia}>
                        <PlaceholderLine width={80} />
                        <PlaceholderLine />
                        <PlaceholderLine width={30} />
                    </Placeholder>
                </View>

                <View visible={visible} animated={true} style={{ padding: 10 }}>
                    <Placeholder Animation={Shine} Left={PlaceholderMedia}>
                        <PlaceholderLine width={80} />
                        <PlaceholderLine />
                        <PlaceholderLine width={30} />
                    </Placeholder>
                </View>

                <View visible={visible} animated={true} style={{ padding: 10 }}>
                    <Placeholder Animation={Shine} Left={PlaceholderMedia}>
                        <PlaceholderLine width={80} />
                        <PlaceholderLine />
                        <PlaceholderLine width={30} />
                    </Placeholder>
                </View>
                <View visible={visible} animated={true} style={{ padding: 10 }}>
                    <Placeholder Animation={Shine} Left={PlaceholderMedia}>
                        <PlaceholderLine width={80} />
                        <PlaceholderLine />
                        <PlaceholderLine width={30} />
                    </Placeholder>
                </View>
                <View visible={visible} animated={true} style={{ padding: 10 }}>
                    <Placeholder Animation={Shine} Left={PlaceholderMedia}>
                        <PlaceholderLine width={80} />
                        <PlaceholderLine />
                        <PlaceholderLine width={30} />
                    </Placeholder>
                </View>
                <View visible={visible} animated={true} style={{ padding: 10 }}>
                    <Placeholder Animation={Shine} Left={PlaceholderMedia}>
                        <PlaceholderLine width={80} />
                        <PlaceholderLine />
                        <PlaceholderLine width={30} />
                    </Placeholder>
                </View>
                <View visible={visible} animated={true} style={{ padding: 10 }}>
                    <Placeholder Animation={Shine} Left={PlaceholderMedia}>
                        <PlaceholderLine width={80} />
                        <PlaceholderLine />
                        <PlaceholderLine width={30} />
                    </Placeholder>
                </View>
            </View>
        )
    } else { return null }

}