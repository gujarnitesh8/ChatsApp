import {
    GET_USER_LOGIN_REQUEST,
    GET_USER_REGISTER_REQUEST,
    UPDATE_PROFILE_REQUEST
} from '../Types'


export const getLoginRequest = (params) => {
    console.log(params, "params in action")
    return {
        type: GET_USER_LOGIN_REQUEST,
        params
    };
}


export const registrationRequest = (params) => {
    console.log(params, "params in action")
    return {
        type: GET_USER_REGISTER_REQUEST,
        params
    };
}

export const updateProfile = (params) => {
    // console.log(params, "params in action")
    return {
        type: UPDATE_PROFILE_REQUEST,
        params
    };
}
